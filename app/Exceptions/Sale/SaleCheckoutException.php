<?php

namespace App\Exceptions\Sale;

use RuntimeException;

class SaleCheckoutException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
        public readonly array $data = [],
    ) {
        parent::__construct($message);
    }
}
