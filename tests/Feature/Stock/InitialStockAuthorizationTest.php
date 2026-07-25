<?php

namespace Tests\Feature\Stock;

use App\Models\BranchStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Gate;

class InitialStockAuthorizationTest extends StockTestCase
{
    public function test_owner_and_admin_policy_access_is_scoped_to_authorized_branch(): void
    {
        $branchA = $this->createBranch('PA01');
        $branchB = $this->createBranch('PB01');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);
        $stockA = $this->createStock($branchA, $this->createProduct());
        $stockB = $this->createStock($branchB, $this->createProduct());

        $this->assertTrue(Gate::forUser($owner)->allows('view', $stockB));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $stockA));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $stockB));
        $this->assertTrue(Gate::forUser($admin)->allows('createInitial', [BranchStock::class, $branchA]));
        $this->assertFalse(Gate::forUser($admin)->allows('createInitial', [BranchStock::class, $branchB]));
        $this->assertFalse(Gate::forUser($admin)->allows('adjust', $stockA));
        $this->assertFalse(Gate::forUser($cashier)->allows('viewAny', BranchStock::class));
    }

    public function test_admin_cannot_store_stock_for_another_branch(): void
    {
        $branchA = $this->createBranch('AA01');
        $branchB = $this->createBranch('AB01');
        $admin = $this->createUser('admin', $branchA);
        $product = $this->createProduct();

        $this->actingAs($admin)
            ->post(route('stocks.initial.store'), $this->initialPayload($branchB, $product))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cashier_cannot_open_or_submit_initial_stock(): void
    {
        $branch = $this->createBranch('AC01');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();

        $this->actingAs($cashier)
            ->get(route('stocks.initial.create'))
            ->assertForbidden();
        $this->actingAs($cashier)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product))
            ->assertForbidden();
        $this->assertDatabaseCount('branch_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inactive_user_and_inactive_branch_are_rejected(): void
    {
        $branch = $this->createBranch('AD01');
        $inactiveAdmin = $this->createUser('admin', $branch, ['is_active' => false]);
        $product = $this->createProduct();

        $this->actingAs($inactiveAdmin)
            ->post(route('stocks.initial.store'), [
                'product_id' => $product->id,
                'quantity' => '5.000',
                'reason' => 'Stok awal toko',
            ])
            ->assertRedirect(route('login'));

        $branch->update(['is_active' => false]);
        $activeAdmin = $this->createUser('admin', $branch);

        $this->actingAs($activeAdmin)
            ->post(route('stocks.initial.store'), [
                'product_id' => $product->id,
                'quantity' => '5.000',
                'reason' => 'Stok awal toko',
            ])
            ->assertForbidden();
    }

    public function test_stock_movement_policy_is_read_only_and_branch_scoped(): void
    {
        $branchA = $this->createBranch('AE01');
        $branchB = $this->createBranch('AF01');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);
        $product = $this->createProduct();
        $movementA = $this->createMovement($branchA, $product, $admin);
        $movementB = $this->createMovement($branchB, $product, $owner);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $movementB));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $movementA));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $movementB));
        $this->assertFalse(Gate::forUser($cashier)->allows('viewAny', StockMovement::class));
        $this->assertFalse(Gate::forUser($owner)->allows('update', $movementA));
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $movementA));
    }
}
