<?php

namespace App\Exceptions\Sale;

class DiscountLimitExceededException extends SaleCheckoutException
{
    public function __construct()
    {
        parent::__construct(
            'CASHIER_DISCOUNT_LIMIT_EXCEEDED',
            'Diskon melebihi batas yang diizinkan untuk Kasir.',
            422,
        );
    }
}
