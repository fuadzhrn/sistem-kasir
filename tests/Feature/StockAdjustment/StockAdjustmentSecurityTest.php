<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

class StockAdjustmentSecurityTest extends StockAdjustmentTestCase
{
    public function test_spoofed_audit_and_movement_fields_are_ignored(): void
    {
        $branch = $this->createBranch('SEC');
        $admin = $this->createUser('admin', $branch);
        $otherUser = $this->createUser('admin', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000', '50000.00');
        $payload = $this->payload($branch, $product);
        unset($payload['branch_id']);
        $payload += [
            'adjustment_number' => 'PALSU',
            'quantity_before' => '999.000',
            'quantity_change' => '-999.000',
            'quantity_after' => '999.000',
            'unit_cost' => '1.00',
            'movement_type' => StockMovement::TYPE_SALE,
            'created_by' => $otherUser->id,
            'reference_type' => 'Palsu',
            'reference_id' => 999999,
        ];

        $this->actingAs($admin)->post(route('stock-adjustments.store'), $payload)->assertRedirect();

        $adjustment = StockAdjustment::query()->sole();
        $movement = StockMovement::query()->sole();
        $this->assertSame($admin->id, $adjustment->created_by);
        $this->assertSame('10.000', $adjustment->quantity_before);
        $this->assertSame('2.000', $adjustment->quantity_change);
        $this->assertSame('12.000', $adjustment->quantity_after);
        $this->assertSame('50000.00', $adjustment->unit_cost);
        $this->assertNotSame('PALSU', $adjustment->adjustment_number);
        $this->assertSame(StockMovement::TYPE_ADJUSTMENT_IN, $movement->movement_type);
        $this->assertSame(StockAdjustment::class, $movement->reference_type);
        $this->assertSame($adjustment->id, $movement->reference_id);
    }

    public function test_admin_html_never_contains_unit_or_average_cost_or_master_purchase_price(): void
    {
        $branch = $this->createBranch('HIDE');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct(['purchase_price' => '918273.00']);
        $adjustment = $this->createAdjustment($branch, $product, $admin, ['unit_cost' => '876543.00']);

        $this->actingAs($admin)->get(route('stock-adjustments.create'))
            ->assertOk()
            ->assertDontSee('918273')
            ->assertDontSee('average_cost')
            ->assertDontSee('unit_cost');

        $this->actingAs($admin)->get(route('stock-adjustments.show', $adjustment))
            ->assertOk()
            ->assertDontSee('876543')
            ->assertDontSee('average_cost')
            ->assertDontSee('unit_cost')
            ->assertDontSee('Biaya modal');
    }

    public function test_csrf_active_branch_active_product_and_query_branch_are_enforced(): void
    {
        $branch = $this->createBranch('SAFE');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct();

        $middleware = Route::getRoutes()->getByName('stock-adjustments.store')?->gatherMiddleware() ?? [];
        $this->assertContains('web', $middleware);
        $this->actingAs($owner)->get(route('stock-adjustments.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->actingAs($admin)->get(route('stock-adjustments.index', ['branch_id' => 999999]))
            ->assertSessionHasErrors('branch_id');

        $branch->update(['is_active' => false]);
        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product))
            ->assertSessionHasErrors('branch_id');

        $branch->update(['is_active' => true]);
        $product->update(['is_active' => false]);
        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product))
            ->assertSessionHasErrors('product_id');
    }

    public function test_reduction_without_branch_stock_is_rejected(): void
    {
        $branch = $this->createBranch('EMPTY');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'adjustment_type' => StockAdjustment::TYPE_LOST,
            'quantity' => '1.000',
        ]))->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
