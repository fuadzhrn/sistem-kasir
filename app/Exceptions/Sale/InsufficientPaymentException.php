<?php

namespace App\Exceptions\Sale;

class InsufficientPaymentException extends SaleCheckoutException
{
    public function __construct()
    {
        parent::__construct(
            'INSUFFICIENT_PAYMENT',
            'Uang diterima belum mencukupi total pembayaran.',
            422,
        );
    }
}
