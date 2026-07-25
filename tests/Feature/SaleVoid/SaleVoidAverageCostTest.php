<?php

namespace Tests\Feature\SaleVoid;

class SaleVoidAverageCostTest extends SaleVoidTestCase
{
    public function test_average_cost_uses_current_stock_and_sale_item_cost_snapshot(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());

        $this->assertSame('58333.33', $stock->fresh()->average_cost);
    }

    public function test_zero_current_stock_uses_returned_cost_snapshot_and_supports_fractional_quantity(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale(
            $branch,
            $cashier,
            itemAttributes: ['quantity' => '2.500', 'cost_price' => '51234.56'],
            stockAttributes: ['quantity' => '0.000', 'average_cost' => '0.00'],
        );

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());

        $this->assertSame('2.500', $stock->fresh()->quantity);
        $this->assertSame('51234.56', $stock->fresh()->average_cost);
    }
}
