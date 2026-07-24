<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PAY-###')),
            'name' => fake()->unique()->word(),
            'type' => 'general',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
