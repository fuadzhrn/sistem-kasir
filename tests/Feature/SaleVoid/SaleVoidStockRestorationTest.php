<?php

namespace Tests\Feature\SaleVoid;

use App\Models\SaleItem;
use App\Models\SaleVoid;
use App\Models\StockMovement;

class SaleVoidStockRestorationTest extends SaleVoidTestCase
{
    public function test_stock_is_restored_once_and_movement_uses_sale_void_reference(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'product' => $product, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());
        $saleVoid = SaleVoid::query()->where('sale_id', $sale->id)->firstOrFail();
        $movement = StockMovement::query()->where('movement_type', StockMovement::TYPE_VOID_SALE)->firstOrFail();

        $this->assertSame('12.000', $stock->fresh()->quantity);
        $this->assertSame($branch->id, $movement->branch_id);
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame('10.000', $movement->quantity_before);
        $this->assertSame('2.000', $movement->quantity_change);
        $this->assertSame('12.000', $movement->quantity_after);
        $this->assertSame(SaleVoid::class, $movement->reference_type);
        $this->assertSame($saleVoid->id, $movement->reference_id);
    }

    public function test_duplicate_product_lines_are_aggregated_into_one_movement(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'product' => $product, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier);
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit_name' => 'Kilogram',
            'quantity' => '1.500',
            'selling_price' => '90000.00',
            'cost_price' => '40000.00',
            'discount_amount' => '0.00',
            'subtotal' => '135000.00',
            'profit' => '75000.00',
        ]);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());

        $this->assertSame('13.500', $stock->fresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['quantity_change' => '3.500']);
    }
}
