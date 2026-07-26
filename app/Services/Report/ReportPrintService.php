<?php

namespace App\Services\Report;

use Illuminate\Http\Exceptions\HttpResponseException;

final class ReportPrintService
{
    public const MAX_ROWS = 2000;

    public function ensureWithinLimit(int $rowCount): void
    {
        if ($rowCount > self::MAX_ROWS) {
            throw new HttpResponseException(response(
                'Hasil laporan melebihi 2.000 baris. Persempit tanggal atau filter sebelum mencetak.',
                422,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            ));
        }
    }
}
