<?php

namespace App\Exceptions\Sale;

class CartPriceChangedException extends SaleCheckoutException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        parent::__construct(
            'CART_PRICE_CHANGED',
            'Harga produk berubah. Keranjang telah diperbarui, silakan periksa lalu bayar kembali.',
            409,
            $data,
        );
    }
}
