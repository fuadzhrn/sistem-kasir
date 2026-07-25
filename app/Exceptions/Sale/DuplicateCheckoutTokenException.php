<?php

namespace App\Exceptions\Sale;

class DuplicateCheckoutTokenException extends SaleCheckoutException
{
    public function __construct()
    {
        parent::__construct(
            'DUPLICATE_CHECKOUT_TOKEN',
            'Token checkout telah digunakan oleh transaksi lain.',
            409,
        );
    }
}
