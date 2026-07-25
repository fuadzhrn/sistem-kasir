<?php

namespace App\Exceptions\Sale;

class SaleCannotBeVoidedException extends SaleVoidException
{
    public function __construct(string $message = 'Transaksi tidak dapat dibatalkan.')
    {
        parent::__construct('SALE_CANNOT_BE_VOIDED', $message);
    }
}
