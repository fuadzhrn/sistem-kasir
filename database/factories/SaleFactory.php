<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'cashier_id' => User::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'invoice_number' => strtoupper(fake()->unique()->bothify('INV-########')),
            'transaction_date' => now(),
            'subtotal' => '75000.00',
            'discount_amount' => '0.00',
            'total' => '75000.00',
            'amount_paid' => '75000.00',
            'change_amount' => '0.00',
            'total_cost' => '50000.00',
            'gross_profit' => '25000.00',
            'payment_method_name' => 'Tunai',
            'status' => Sale::STATUS_COMPLETED,
            'notes' => null,
            'voided_at' => null,
        ];
    }
}
