<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['code' => 'UTM'],
            [
                'name' => 'Toko Utama',
                'address' => null,
                'phone' => null,
                'is_active' => true,
            ],
        );
    }
}
