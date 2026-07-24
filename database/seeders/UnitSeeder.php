<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Botol', 'slug' => 'botol', 'symbol' => null],
            ['name' => 'Bungkus', 'slug' => 'bungkus', 'symbol' => null],
            ['name' => 'Sachet', 'slug' => 'sachet', 'symbol' => null],
            ['name' => 'Liter', 'slug' => 'liter', 'symbol' => 'L'],
            ['name' => 'Mililiter', 'slug' => 'mililiter', 'symbol' => 'ml'],
            ['name' => 'Kilogram', 'slug' => 'kilogram', 'symbol' => 'kg'],
            ['name' => 'Gram', 'slug' => 'gram', 'symbol' => 'g'],
            ['name' => 'Karung', 'slug' => 'karung', 'symbol' => null],
            ['name' => 'Dus', 'slug' => 'dus', 'symbol' => null],
            ['name' => 'Kaleng', 'slug' => 'kaleng', 'symbol' => null],
            ['name' => 'Jeriken', 'slug' => 'jeriken', 'symbol' => null],
            ['name' => 'Unit', 'slug' => 'unit', 'symbol' => null],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'symbol' => null],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(
                ['slug' => $unit['slug']],
                [...$unit, 'is_active' => true],
            );
        }
    }
}
