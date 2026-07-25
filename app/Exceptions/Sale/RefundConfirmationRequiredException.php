<?php

namespace App\Exceptions\Sale;

class RefundConfirmationRequiredException extends SaleVoidException
{
    public function __construct()
    {
        parent::__construct(
            'REFUND_CONFIRMATION_REQUIRED',
            'Konfirmasi pengembalian dana manual wajib diberikan untuk pembayaran non-tunai.',
            422,
        );
    }
}
