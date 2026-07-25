<?php

namespace Tests\Feature\Stock;

use App\Models\BranchStock;
use App\Models\StockMovement;

class InitialStockTest extends StockTestCase
{
    public function test_owner_can_store_initial_stock_with_backend_derived_audit_values(): void
    {
        $branch = $this->createBranch('I001');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['purchase_price' => '12500.00']);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, [
                'quantity' => '10.250',
                'movement_type' => StockMovement::TYPE_PURCHASE,
                'quantity_before' => '999.000',
                'quantity_change' => '999.000',
                'quantity_after' => '999.000',
                'unit_cost' => '1.00',
                'average_cost' => '1.00',
                'created_by' => 999999,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '10.250',
            'average_cost' => '12500.00',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'created_by' => $owner->id,
            'movement_type' => StockMovement::TYPE_INITIAL,
            'quantity_before' => '0.000',
            'quantity_change' => '10.250',
            'quantity_after' => '10.250',
            'unit_cost' => '12500.00',
        ]);
    }

    public function test_owner_can_store_different_initial_stock_for_the_same_product_in_two_branches(): void
    {
        $branchA = $this->createBranch('I009');
        $branchB = $this->createBranch('I010');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branchA, $product, ['quantity' => '8.000']))
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branchB, $product, ['quantity' => '3.000']))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'quantity' => '8.000',
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'quantity' => '3.000',
        ]);
        $this->assertDatabaseCount('branch_stocks', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_admin_can_store_stock_only_for_account_branch_without_branch_id(): void
    {
        $branch = $this->createBranch('I002');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct();
        $payload = $this->initialPayload($branch, $product);
        unset($payload['branch_id']);

        $this->actingAs($admin)
            ->post(route('stocks.initial.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '10.000',
        ]);
    }

    public function test_initial_stock_validates_active_records_quantity_precision_and_reason(): void
    {
        $branch = $this->createBranch('I003');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, [
                'quantity' => '-1',
                'reason' => 'abc',
            ]))
            ->assertSessionHasErrors(['quantity', 'reason']);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, [
                'quantity' => '1.2345',
            ]))
            ->assertSessionHasErrors('quantity');

        $inactiveBranch = $this->createBranch('I004', ['is_active' => false]);
        $inactiveProduct = $this->createProduct(['is_active' => false]);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($inactiveBranch, $product))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $inactiveProduct))
            ->assertSessionHasErrors('product_id');
    }

    public function test_positive_initial_stock_is_rejected_when_reference_cost_is_unavailable(): void
    {
        $branch = $this->createBranch('I005');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['purchase_price' => '0.00']);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_initial_stock_can_be_corrected_up_down_and_to_zero_without_mutating_old_movements(): void
    {
        $branch = $this->createBranch('I006');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        foreach (['10.000', '12.000', '7.000', '0.000'] as $quantity) {
            $this->actingAs($owner)
                ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, [
                    'quantity' => $quantity,
                    'reason' => "Koreksi menjadi {$quantity}",
                ]))
                ->assertRedirect();
        }

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '0.000',
        ]);
        $this->assertSame(4, StockMovement::query()->count());
        $this->assertDatabaseHas('stock_movements', [
            'quantity_before' => '10.000',
            'quantity_change' => '2.000',
            'quantity_after' => '12.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'quantity_before' => '12.000',
            'quantity_change' => '-5.000',
            'quantity_after' => '7.000',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'quantity_before' => '7.000',
            'quantity_change' => '-7.000',
            'quantity_after' => '0.000',
        ]);
    }

    public function test_same_quantity_is_rejected_without_creating_empty_movement(): void
    {
        $branch = $this->createBranch('I007');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $this->createMovement($branch, $product, $owner);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product))
            ->assertSessionHasErrors('quantity');

        $this->assertSame('10.000', $stock->refresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_correction_is_rejected_after_any_operational_movement(): void
    {
        $types = [
            StockMovement::TYPE_PURCHASE,
            StockMovement::TYPE_SALE,
            StockMovement::TYPE_ADJUSTMENT_IN,
            StockMovement::TYPE_ADJUSTMENT_OUT,
            StockMovement::TYPE_TRANSFER_IN,
            StockMovement::TYPE_TRANSFER_OUT,
            StockMovement::TYPE_VOID_SALE,
        ];

        foreach ($types as $index => $type) {
            $branch = $this->createBranch('OP'.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
            $owner = $this->createUser('owner');
            $product = $this->createProduct();
            $stock = $this->createStock($branch, $product);
            $this->createMovement($branch, $product, $owner, $type);

            $this->actingAs($owner)
                ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, ['quantity' => '12.000']))
                ->assertSessionHasErrors('quantity');

            $this->assertSame('10.000', $stock->refresh()->quantity);
        }
    }

    public function test_product_and_minimum_stock_are_not_modified(): void
    {
        $branch = $this->createBranch('I008');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['minimum_stock' => '9.000', 'name' => 'Produk Tetap']);

        $this->actingAs($owner)
            ->post(route('stocks.initial.store'), $this->initialPayload($branch, $product, [
                'minimum_stock' => '999.000',
                'name' => 'Nama Disusupi',
            ]))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('9.000', $product->minimum_stock);
        $this->assertSame('Produk Tetap', $product->name);
        $this->assertSame(1, BranchStock::query()->count());
    }
}
