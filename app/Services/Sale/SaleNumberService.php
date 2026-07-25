<?php

namespace App\Services\Sale;

use App\Exceptions\Sale\SaleCheckoutException;
use App\Models\Branch;
use App\Models\Sale;
use Carbon\CarbonInterface;

class SaleNumberService
{
    public function generate(Branch $branch, CarbonInterface $transactionDate): string
    {
        $lockedBranch = Branch::query()
            ->lockForUpdate()
            ->findOrFail($branch->getKey());
        $branchCode = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($lockedBranch->code))
            ?: 'CBG'.$lockedBranch->getKey();
        $prefix = $branchCode.'-'.$transactionDate->format('Ymd').'-';
        $lastNumber = Sale::query()
            ->where('branch_id', $lockedBranch->getKey())
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $sequence = $lastNumber === null
            ? 1
            : ((int) substr((string) $lastNumber, -4)) + 1;

        if ($sequence > 9999) {
            throw new SaleCheckoutException(
                'CHECKOUT_FAILED',
                'Nomor transaksi untuk cabang dan tanggal ini telah mencapai batas.',
                500,
            );
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
