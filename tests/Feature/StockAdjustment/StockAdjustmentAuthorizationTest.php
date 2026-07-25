<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Gate;

class StockAdjustmentAuthorizationTest extends StockAdjustmentTestCase
{
    public function test_guest_cashier_and_inactive_user_are_denied(): void
    {
        $branch = $this->createBranch('AUTH');
        $cashier = $this->createUser('cashier', $branch);
        $inactiveOwner = $this->createUser('owner', null, ['is_active' => false]);

        $this->get(route('stock-adjustments.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('stock-adjustments.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('stock-adjustments.create'))->assertForbidden();
        $this->assertTrue(Gate::forUser($inactiveOwner)->denies('viewAny', StockAdjustment::class));
    }

    public function test_admin_cannot_spoof_or_view_another_branch(): void
    {
        $branchA = $this->createBranch('AA');
        $branchB = $this->createBranch('AB');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branchA, $product);
        $other = $this->createAdjustment($branchB, $product, $owner);

        $this->actingAs($admin)->post(route('stock-adjustments.store'), $this->payload($branchB, $product))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($admin)->get(route('stock-adjustments.show', $other))->assertForbidden();
        $this->assertSame('10.000', $this->createStock($branchB, $product)->quantity);
    }

    public function test_adjustments_are_immutable_and_have_no_mutation_routes(): void
    {
        $branch = $this->createBranch('IMM');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct();
        $adjustment = $this->createAdjustment($branch, $product, $owner);

        $this->assertFalse(Gate::forUser($owner)->allows('update', $adjustment));
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $adjustment));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $adjustment));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $adjustment));
        $this->assertFalse(app('router')->has('stock-adjustments.edit'));
        $this->assertFalse(app('router')->has('stock-adjustments.update'));
        $this->assertFalse(app('router')->has('stock-adjustments.destroy'));

        $this->actingAs($owner)->get(route('stock-adjustments.show', $adjustment))
            ->assertOk()
            ->assertDontSee('>Edit<', false)
            ->assertDontSee('>Hapus<', false);
    }
}
