<?php

namespace App\Exceptions\Sale;

class StockCostNotReadyException extends SaleCheckoutException
{
    public function __construct()
    {
        parent::__construct(
            'STOCK_COST_NOT_READY',
            'Data harga modal stok belum siap. Silakan hubungi Owner.',
            409,
        );
    }
}
