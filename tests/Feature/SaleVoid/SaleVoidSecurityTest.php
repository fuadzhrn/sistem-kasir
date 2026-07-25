<?php

namespace Tests\Feature\SaleVoid;

class SaleVoidSecurityTest extends SaleVoidTestCase
{
    public function test_client_financial_stock_status_and_actor_fields_are_ignored(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $attacker = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier);
        $payload = $this->voidPayload(false, [
            'branch_id' => 999999,
            'status' => 'voided',
            'voided_by' => $attacker->id,
            'quantity' => '999.000',
            'cost_price' => '1.00',
            'average_cost' => '1.00',
            'total_cost' => '1.00',
            'gross_profit' => '1.00',
            'movement_type' => 'purchase',
        ]);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'branch_id' => $branch->id,
            'voided_by' => $cashier->id,
            'original_total_cost' => '100000.00',
            'original_gross_profit' => '80000.00',
        ]);
        $this->assertSame('12.000', $stock->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'void_sale',
            'quantity_change' => '2.000',
            'unit_cost' => '50000.00',
        ]);
    }

    public function test_cashier_json_response_and_page_do_not_expose_cost_or_profit(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patchJson(route('sales.void', $sale), $this->voidPayload())
            ->assertOk()
            ->assertJsonMissingPath('data.original_total_cost')
            ->assertJsonMissingPath('data.original_gross_profit')
            ->assertJsonMissingPath('data.average_cost');
        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertDontSee('Total HPP')
            ->assertDontSee('Laba kotor');
    }

    public function test_route_has_csrf_rate_limit_and_no_delete_or_approval_endpoints(): void
    {
        $route = app('router')->getRoutes()->getByName('sales.void');
        $middleware = $route?->gatherMiddleware() ?? [];

        $this->assertContains('web', $middleware);
        $this->assertContains('throttle:10,1', $middleware);
        $this->assertFalse(app('router')->has('sales.destroy'));
        $this->assertFalse(app('router')->has('sale-voids.destroy'));
        $this->assertFalse(app('router')->has('sales.void.approve'));
        $this->assertFalse(app('router')->has('sales.void.reject'));
    }
}
