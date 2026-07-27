<?php

namespace App\Services\Sale;

use App\Exceptions\Sale\SaleCheckoutException;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleNumberSequence;
use App\Services\Setting\StoreSettingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SaleNumberService
{
    public function __construct(private readonly StoreSettingService $settings) {}

    public function generate(Branch $branch, CarbonInterface $transactionDate): string
    {
        return DB::transaction(function () use ($branch, $transactionDate): string {
            $lockedBranch = Branch::query()
                ->lockForUpdate()
                ->findOrFail($branch->getKey());
            $date = $transactionDate->toDateString();
            $counter = SaleNumberSequence::query()
                ->where('branch_id', $lockedBranch->getKey())
                ->whereDate('sequence_date', $date)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $existingInvoices = Sale::query()
                    ->where('branch_id', $lockedBranch->getKey())
                    ->whereDate('transaction_date', $date)
                    ->pluck('invoice_number');
                $lastLegacySequence = $existingInvoices
                    ->map(static function (string $invoice): int {
                        preg_match('/(\d{4,6})$/', $invoice, $matches);

                        return isset($matches[1]) ? (int) $matches[1] : 0;
                    })
                    ->max() ?? 0;
                $counter = SaleNumberSequence::query()->create([
                    'branch_id' => $lockedBranch->getKey(),
                    'sequence_date' => $date,
                    'last_number' => max($existingInvoices->count(), $lastLegacySequence),
                ]);
            }

            $sequence = $counter->last_number + 1;
            $numberSettings = $this->settings->receiptNumberSettings();
            $maximum = (10 ** $numberSettings['digits']) - 1;

            if ($sequence > $maximum) {
                throw new SaleCheckoutException(
                    'CHECKOUT_FAILED',
                    'Nomor transaksi untuk cabang dan tanggal ini telah mencapai batas.',
                    500,
                );
            }

            $counter->update(['last_number' => $sequence]);

            return $this->format(
                (string) $lockedBranch->code,
                $transactionDate,
                $sequence,
                $numberSettings,
            );
        }, 3);
    }

    /**
     * @param  array{format: string, prefix: string|null, separator: string, digits: int}  $settings
     */
    private function format(
        string $branchCode,
        CarbonInterface $date,
        int $sequence,
        array $settings,
    ): string {
        $branchCode = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($branchCode))
            ?: 'CBG';
        $parts = [$branchCode, $date->format('Ymd'), str_pad(
            (string) $sequence,
            $settings['digits'],
            '0',
            STR_PAD_LEFT,
        )];

        if (str_starts_with($settings['format'], 'prefix_') && $settings['prefix'] !== null) {
            array_unshift($parts, $settings['prefix']);
        }

        return implode($settings['separator'], $parts);
    }
}
