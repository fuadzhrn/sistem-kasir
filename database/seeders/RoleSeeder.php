<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Pemilik dengan akses lintas cabang.',
            ],
            [
                'name' => 'Admin/Kepala Cabang',
                'slug' => 'admin',
                'description' => 'Pengelola operasional pada cabang.',
            ],
            [
                'name' => 'Kasir/Pegawai',
                'slug' => 'cashier',
                'description' => 'Pelaksana operasional dan transaksi kasir.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [...$role, 'is_active' => true],
            );
        }
    }
}
