<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'symbol' => fake()->optional()->lexify('??'),
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ];
    }
}
