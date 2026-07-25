<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pestisida', 'slug' => 'pestisida'],
            ['name' => 'Herbisida', 'slug' => 'herbisida'],
            ['name' => 'Fungisida', 'slug' => 'fungisida'],
            ['name' => 'Insektisida', 'slug' => 'insektisida'],
            ['name' => 'Pupuk', 'slug' => 'pupuk'],
            ['name' => 'Benih', 'slug' => 'benih'],
            ['name' => 'Perlengkapan Pertanian', 'slug' => 'perlengkapan-pertanian'],
            ['name' => 'Lainnya', 'slug' => 'lainnya'],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
