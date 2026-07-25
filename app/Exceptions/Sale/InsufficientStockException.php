<?php

namespace App\Exceptions\Sale;

class InsufficientStockException extends SaleCheckoutException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        parent::__construct(
            'INSUFFICIENT_STOCK',
            'Stok salah satu produk tidak mencukupi.',
            409,
            $data,
        );
    }
}
