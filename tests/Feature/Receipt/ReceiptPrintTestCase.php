<?php

namespace Tests\Feature\Receipt;

use Tests\Feature\SaleHistory\SaleHistoryTestCase;

abstract class ReceiptPrintTestCase extends SaleHistoryTestCase
{
    protected function printUrl(int|string $sale, array $query = []): string
    {
        return route('receipts.print', ['sale' => $sale, ...$query]);
    }
}
