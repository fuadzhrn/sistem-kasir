<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'code' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'barcode' => fake()->unique()->ean13(),
            'name' => fake()->words(3, true),
            'brand' => fake()->optional()->company(),
            'size' => fake()->optional()->randomElement(['100 ml', '500 ml', '1 kg']),
            'purchase_price' => (string) fake()->numberBetween(1000, 100000),
            'selling_price' => (string) fake()->numberBetween(1000, 150000),
            'minimum_stock' => fake()->numberBetween(0, 20).'.000',
            'image_path' => null,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
