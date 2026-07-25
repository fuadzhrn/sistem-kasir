<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'changed_by' => User::factory(),
            'old_purchase_price' => '50000.00',
            'new_purchase_price' => '55000.00',
            'old_selling_price' => '75000.00',
            'new_selling_price' => '80000.00',
            'reason' => fake()->optional()->sentence(),
            'changed_at' => now(),
        ];
    }
}
