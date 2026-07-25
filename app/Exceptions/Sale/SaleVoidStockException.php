<?php

namespace App\Exceptions\Sale;

class SaleVoidStockException extends SaleVoidException
{
    public function __construct()
    {
        parent::__construct(
            'SALE_VOID_STOCK_ERROR',
            'Stok transaksi tidak konsisten. Pembatalan belum diterapkan.',
        );
    }
}
