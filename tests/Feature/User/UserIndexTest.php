<?php

namespace Tests\Feature\User;

use App\Models\Branch;

class UserIndexTest extends UserTestCase
{
    public function test_owner_can_search_filter_and_paginate_all_users(): void
    {
        $owner = $this->createUser('owner', null, ['name' => 'Owner Utama']);
        $branchA = Branch::factory()->create(['name' => 'Cabang Alfa']);
        $branchB = Branch::factory()->create(['name' => 'Cabang Beta']);
        $this->createUser('admin', $branchA, ['name' => 'Admin Dicari', 'username' => 'admin.dicari']);
        $this->createUser('cashier', $branchB, ['name' => 'Kasir Nonaktif', 'is_active' => false]);
        for ($index = 0; $index < 14; $index++) {
            $this->createUser('cashier', $branchA);
        }

        $this->actingAs($owner)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSeeText('Manajemen Pengguna')
            ->assertSee('aria-label="Halaman berikutnya"', false);

        $this->actingAs($owner)
            ->get(route('users.index', ['search' => 'admin.dicari', 'role' => 'admin', 'branch' => $branchA->id, 'status' => 'active']))
            ->assertOk()
            ->assertSeeText('Admin Dicari')
            ->assertDontSeeText('Kasir Nonaktif');
    }

    public function test_admin_only_sees_non_owner_users_in_own_branch_read_only(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $owner = $this->createUser('owner', null, ['name' => 'Owner Rahasia']);
        $admin = $this->createUser('admin', $branchA, ['name' => 'Admin A']);
        $cashierA = $this->createUser('cashier', $branchA, ['name' => 'Kasir A']);
        $cashierB = $this->createUser('cashier', $branchB, ['name' => 'Kasir B']);

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();

        $response
            ->assertSeeText($admin->name)
            ->assertSeeText($cashierA->name)
            ->assertDontSeeText($owner->name)
            ->assertDontSeeText($cashierB->name)
            ->assertDontSeeText('Tambah Pengguna')
            ->assertDontSeeText('Reset Password')
            ->assertDontSeeText('Nonaktifkan');
    }

    public function test_cashier_cannot_open_user_module(): void
    {
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
    }
}
