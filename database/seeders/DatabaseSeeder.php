<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BranchSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            PaymentMethodSeeder::class,
            ExpenseCategorySeeder::class,
            SettingSeeder::class,
        ]);
    }
}
