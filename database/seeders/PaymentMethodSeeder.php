<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            ['code' => 'CASH', 'name' => 'Tunai', 'type' => 'cash', 'sort_order' => 1],
            ['code' => 'TRANSFER', 'name' => 'Transfer Bank', 'type' => 'non_cash', 'sort_order' => 2],
            ['code' => 'QRIS', 'name' => 'QRIS', 'type' => 'non_cash', 'sort_order' => 3],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $paymentMethod['code']],
                [...$paymentMethod, 'is_active' => true],
            );
        }
    }
}
