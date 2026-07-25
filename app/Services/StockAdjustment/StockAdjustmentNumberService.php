<?php

namespace App\Services\StockAdjustment;

use App\Models\Branch;
use App\Models\StockAdjustment;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class StockAdjustmentNumberService
{
    public function generate(Branch $branch, CarbonInterface $date): string
    {
        $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());

        if (! $lockedBranch->is_active) {
            throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
        }

        $branchCode = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($lockedBranch->code)) ?: 'CBG';
        $prefix = 'ADJ-'.$branchCode.'-'.$date->format('Ymd').'-';
        $lastNumber = StockAdjustment::query()
            ->where('branch_id', $lockedBranch->getKey())
            ->where('adjustment_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('adjustment_number')
            ->value('adjustment_number');
        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, -4)) + 1;

        if ($sequence > 9999) {
            throw ValidationException::withMessages([
                'adjustment_type' => 'Nomor penyesuaian cabang untuk hari ini telah mencapai batas.',
            ]);
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
