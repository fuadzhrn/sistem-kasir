<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Sale;

class BranchManagementTest extends BranchTestCase
{
    public function test_owner_can_create_branch_with_normalized_unique_code(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->post(route('branches.store'), [
                'code' => '  cab-01  ',
                'name' => ' Cabang Baru ',
                'address' => ' Alamat ',
                'phone' => '0812-0001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'code' => 'CAB-01',
            'name' => 'Cabang Baru',
            'phone' => '0812-0001',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('branches.store'), ['code' => 'cab-01', 'name' => 'Duplikat'])
            ->assertSessionHasErrors('code');
        $this->actingAs($owner)
            ->post(route('branches.store'), ['code' => '', 'name' => ''])
            ->assertSessionHasErrors(['code', 'name']);
    }

    public function test_owner_can_edit_branch_but_cannot_change_code_after_sale(): void
    {
        $owner = $this->createUser('owner');
        $branch = Branch::factory()->create(['code' => 'LAMA']);

        $this->actingAs($owner)
            ->put(route('branches.update', $branch), [
                'code' => 'baru',
                'name' => 'Nama Baru',
                'address' => null,
                'phone' => '021-123',
            ])
            ->assertRedirect(route('branches.show', $branch));
        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'code' => 'BARU']);

        $cashier = $this->createUser('cashier', $branch);
        Sale::factory()->create(['branch_id' => $branch, 'cashier_id' => $cashier]);

        $this->actingAs($owner)
            ->put(route('branches.update', $branch), [
                'code' => 'GANTI',
                'name' => 'Nama Tetap Bisa',
                'address' => null,
                'phone' => null,
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'code' => 'BARU']);
    }

    public function test_branch_status_respects_active_user_protection_and_can_be_reactivated(): void
    {
        $owner = $this->createUser('owner');
        $emptyBranch = Branch::factory()->create();

        $this->actingAs($owner)
            ->patch(route('branches.status.update', $emptyBranch), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($emptyBranch->fresh()->is_active);

        $this->actingAs($owner)
            ->patch(route('branches.status.update', $emptyBranch), ['is_active' => true])
            ->assertRedirect();
        $this->assertTrue($emptyBranch->fresh()->is_active);

        $occupiedBranch = Branch::factory()->create();
        $this->createUser('admin', $occupiedBranch);

        $this->actingAs($owner)
            ->patch(route('branches.status.update', $occupiedBranch), ['is_active' => false])
            ->assertSessionHasErrors('is_active');
        $this->assertTrue($occupiedBranch->fresh()->is_active);
    }
}
