<?php

namespace App\Services\StockReceipt;

use App\Models\Branch;
use App\Models\StockReceipt;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class StockReceiptNumberService
{
    public function generate(Branch $branch, CarbonInterface $receiptDate): string
    {
        $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());

        if (! $lockedBranch->is_active) {
            throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
        }

        $branchCode = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($lockedBranch->code)) ?: 'CBG';
        $prefix = 'BM-'.$branchCode.'-'.$receiptDate->format('Ymd').'-';
        $lastNumber = StockReceipt::query()
            ->where('branch_id', $lockedBranch->getKey())
            ->where('receipt_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('receipt_number')
            ->value('receipt_number');
        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, -4)) + 1;

        if ($sequence > 9999) {
            throw ValidationException::withMessages([
                'receipt_date' => 'Nomor penerimaan untuk cabang dan tanggal ini telah mencapai batas.',
            ]);
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
