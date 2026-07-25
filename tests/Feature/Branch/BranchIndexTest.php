<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;

class BranchIndexTest extends BranchTestCase
{
    public function test_owner_can_search_filter_and_paginate_branches(): void
    {
        $owner = $this->createUser('owner');
        Branch::factory()->create(['code' => 'KHUSUS-A', 'name' => 'Cabang Khusus', 'is_active' => true]);
        Branch::factory()->create(['code' => 'NONAKTIF', 'name' => 'Cabang Tutup', 'is_active' => false]);
        Branch::factory()->count(14)->create();

        $this->actingAs($owner)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSeeText('Manajemen Cabang')
            ->assertSee('aria-label="Halaman berikutnya"', false);

        $this->actingAs($owner)
            ->get(route('branches.index', ['search' => 'KHUSUS']))
            ->assertOk()
            ->assertSeeText('Cabang Khusus')
            ->assertDontSeeText('Cabang Tutup');

        $this->actingAs($owner)
            ->get(route('branches.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSeeText('Cabang Tutup')
            ->assertDontSeeText('Cabang Khusus');
    }

    public function test_admin_uses_read_only_my_branch_and_cashier_is_denied(): void
    {
        $branch = Branch::factory()->create(['name' => 'Cabang Admin']);
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($admin)->get(route('branches.index'))->assertForbidden();
        $this->actingAs($admin)
            ->get(route('my-branch.show'))
            ->assertOk()
            ->assertSeeText('Cabang Admin')
            ->assertSeeText('read-only');

        $this->actingAs($cashier)->get(route('branches.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('my-branch.show'))->assertForbidden();
    }
}
