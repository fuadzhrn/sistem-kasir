<?php

namespace App\Services\StockTransfer;

use App\Models\Branch;
use App\Models\StockTransfer;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class StockTransferNumberService
{
    public function generate(Branch $source, Branch $destination, CarbonInterface $date): string
    {
        $sourceCode = $this->normalizeCode($source->code);
        $destinationCode = $this->normalizeCode($destination->code);
        $prefix = 'TRF-'.$sourceCode.'-'.$destinationCode.'-'.$date->format('Ymd').'-';
        $lastNumber = StockTransfer::query()
            ->where('from_branch_id', $source->getKey())
            ->where('to_branch_id', $destination->getKey())
            ->where('transfer_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('transfer_number')
            ->value('transfer_number');
        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, -4)) + 1;

        if ($sequence > 9999) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Nomor mutasi untuk rute cabang hari ini telah mencapai batas.',
            ]);
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($code)) ?: 'CBG';
    }
}
