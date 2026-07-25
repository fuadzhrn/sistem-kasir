<?php

namespace App\Exceptions\Sale;

class DiscountLimitExceededException extends SaleCheckoutException
{
    public function __construct()
    {
        parent::__construct(
            'DISCOUNT_LIMIT_EXCEEDED',
            'Diskon melebihi batas yang diizinkan untuk akun Anda.',
            422,
        );
    }
}
