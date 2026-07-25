<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LocalAuthorizationTestAccountSeeder extends Seeder
{
    private const TEST_PASSWORD = 'TestKasir#2026';

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(
                'Seeder akun testing hanya boleh dijalankan pada environment local.',
            );
        }

        $roles = Role::query()
            ->whereIn('slug', ['owner', 'admin', 'cashier'])
            ->get()
            ->keyBy('slug');

        if ($roles->count() !== 3) {
            throw new RuntimeException(
                'Role owner, admin, dan cashier harus tersedia sebelum membuat akun testing.',
            );
        }

        $branchA = Branch::query()->updateOrCreate(
            ['code' => 'TEST-A'],
            [
                'name' => 'Cabang Testing A',
                'address' => 'Data lokal untuk pengujian otorisasi.',
                'phone' => null,
                'is_active' => true,
            ],
        );

        $branchB = Branch::query()->updateOrCreate(
            ['code' => 'TEST-B'],
            [
                'name' => 'Cabang Testing B',
                'address' => 'Data lokal untuk pengujian otorisasi.',
                'phone' => null,
                'is_active' => true,
            ],
        );

        $accounts = [
            [
                'username' => 'test.owner',
                'name' => 'Owner Testing',
                'email' => 'owner.testing@example.test',
                'role_id' => $roles->get('owner')->getKey(),
                'branch_id' => null,
            ],
            [
                'username' => 'test.admin.a',
                'name' => 'Admin Testing Cabang A',
                'email' => 'admin.a.testing@example.test',
                'role_id' => $roles->get('admin')->getKey(),
                'branch_id' => $branchA->getKey(),
            ],
            [
                'username' => 'test.admin.b',
                'name' => 'Admin Testing Cabang B',
                'email' => 'admin.b.testing@example.test',
                'role_id' => $roles->get('admin')->getKey(),
                'branch_id' => $branchB->getKey(),
            ],
            [
                'username' => 'test.kasir.a',
                'name' => 'Kasir Testing Cabang A',
                'email' => 'kasir.a.testing@example.test',
                'role_id' => $roles->get('cashier')->getKey(),
                'branch_id' => $branchA->getKey(),
            ],
            [
                'username' => 'test.kasir.b',
                'name' => 'Kasir Testing Cabang B',
                'email' => 'kasir.b.testing@example.test',
                'role_id' => $roles->get('cashier')->getKey(),
                'branch_id' => $branchB->getKey(),
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    ...$account,
                    'password' => Hash::make(self::TEST_PASSWORD),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => null,
                    'remember_token' => null,
                ],
            );
        }
    }
}
