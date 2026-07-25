<?php

namespace Tests\Feature\Stock;

use App\Models\StockMovement;

class StockSecurityTest extends StockTestCase
{
    public function test_spoofed_audit_and_cost_fields_are_ignored(): void
    {
        $branch = $this->createBranch('SEC1');
        $admin = $this->createUser('admin', $branch);
        $other = $this->createUser('admin', $branch);
        $product = $this->createProduct(['purchase_price' => '15000.00']);

        $this->actingAs($admin)
            ->post(route('stocks.initial.store'), [
                'product_id' => $product->id,
                'quantity' => '4.500',
                'reason' => 'Input aman audit',
                'quantity_before' => '999.000',
                'quantity_change' => '-999.000',
                'quantity_after' => '999.000',
                'movement_type' => StockMovement::TYPE_PURCHASE,
                'created_by' => $other->id,
                'unit_cost' => '1.00',
                'average_cost' => '1.00',
                'reference_type' => 'App\\Models\\Sale',
                'reference_id' => 999,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'created_by' => $admin->id,
            'movement_type' => StockMovement::TYPE_INITIAL,
            'reference_type' => null,
            'reference_id' => null,
            'quantity_before' => '0.000',
            'quantity_change' => '4.500',
            'quantity_after' => '4.500',
            'unit_cost' => '15000.00',
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'average_cost' => '15000.00',
            'quantity' => '4.500',
        ]);
    }

    public function test_admin_cross_branch_manipulation_does_not_change_either_branch(): void
    {
        $branchA = $this->createBranch('SEC2');
        $branchB = $this->createBranch('SEC3');
        $admin = $this->createUser('admin', $branchA);
        $product = $this->createProduct();
        $stockB = $this->createStock($branchB, $product, '20.000');

        $this->actingAs($admin)
            ->post(route('stocks.initial.store'), $this->initialPayload($branchB, $product, [
                'quantity' => '99.000',
            ]))
            ->assertSessionHasErrors('branch_id');

        $this->assertSame('20.000', $stockB->refresh()->quantity);
        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_csrf_middleware_remains_registered_for_web_stock_routes(): void
    {
        $route = app('router')->getRoutes()->getByName('stocks.initial.store');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('active.user', $route->gatherMiddleware());
        $this->assertContains('role:owner,admin', $route->gatherMiddleware());
    }

    public function test_admin_detail_does_not_render_average_or_unit_cost(): void
    {
        $branch = $this->createBranch('SEC4');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct(['purchase_price' => '44444444.00']);
        $stock = $this->createStock($branch, $product, '8.000', '33333333.00');
        $this->createMovement($branch, $product, $admin, attributes: ['unit_cost' => '44444444.00']);

        $this->actingAs($admin)
            ->get(route('stocks.show', $stock))
            ->assertOk()
            ->assertDontSee('33333333')
            ->assertDontSee('44444444')
            ->assertDontSee('Biaya Rata-rata Referensi')
            ->assertDontSee('unit_cost')
            ->assertDontSee('average_cost');
    }

    public function test_stock_pages_do_not_expose_sensitive_application_information(): void
    {
        $branch = $this->createBranch('SEC5');
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id]));

        $response->assertOk()
            ->assertDontSee('APP_KEY')
            ->assertDontSee('DB_PASSWORD')
            ->assertDontSee(base_path())
            ->assertDontSee('SQLSTATE');
    }
}
