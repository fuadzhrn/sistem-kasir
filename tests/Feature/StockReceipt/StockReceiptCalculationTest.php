<?php

namespace Tests\Feature\StockReceipt;

use App\Models\BranchStock;
use App\Models\PriceHistory;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;

class StockReceiptCalculationTest extends StockReceiptTestCase
{
    public function test_receipt_updates_stock_weighted_cost_snapshots_and_movement_atomically(): void
    {
        $branch = $this->createBranch('HPP');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'purchase_price' => '45000.00',
            'selling_price' => '75000.00',
            'minimum_stock' => '7.000',
        ]);
        $this->createStock($branch, $product, '10.000', '50000.00');

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product))
            ->assertRedirect();

        $receipt = StockReceipt::query()->sole();
        $this->assertSame('300000.00', $receipt->total_cost);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '15.000',
            'average_cost' => '53333.33',
        ]);
        $this->assertDatabaseHas('stock_receipt_items', [
            'stock_receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => '5.000',
            'purchase_price' => '60000.00',
            'subtotal' => '300000.00',
            'quantity_before' => '10.000',
            'quantity_after' => '15.000',
            'average_cost_before' => '50000.00',
            'average_cost_after' => '53333.33',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'created_by' => $owner->id,
            'movement_type' => StockMovement::TYPE_PURCHASE,
            'reference_type' => StockReceipt::class,
            'reference_id' => $receipt->id,
            'quantity_before' => '10.000',
            'quantity_change' => '5.000',
            'quantity_after' => '15.000',
            'unit_cost' => '60000.00',
        ]);
        $this->assertStringContainsString($receipt->receipt_number, StockMovement::query()->sole()->notes);

        $product->refresh();
        $this->assertSame('45000.00', $product->purchase_price);
        $this->assertSame('75000.00', $product->selling_price);
        $this->assertSame('7.000', $product->minimum_stock);
        $this->assertSame(0, PriceHistory::query()->count());
    }

    public function test_new_stock_uses_incoming_price_and_fractional_quantities_are_calculated_exactly(): void
    {
        $branch = $this->createBranch('FRAC');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '2.500',
                'purchase_price' => '20000.00',
            ]],
        ]))->assertRedirect();

        $stock = BranchStock::query()->sole();
        $this->assertSame('2.500', $stock->quantity);
        $this->assertSame('20000.00', $stock->average_cost);
        $this->assertSame('50000.00', StockReceiptItem::query()->sole()->subtotal);
    }

    public function test_multiple_products_are_calculated_separately_without_touching_another_branch(): void
    {
        $branchA = $this->createBranch('MA');
        $branchB = $this->createBranch('MB');
        $owner = $this->createUser('owner');
        $first = $this->createProduct(['code' => 'P-001']);
        $second = $this->createProduct(['code' => 'P-002']);
        $this->createStock($branchA, $first, '10.000', '50000.00');
        $this->createStock($branchA, $second, '2.500', '20000.00');
        $otherStock = $this->createStock($branchB, $first, '99.000', '12345.00');

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchA, $first, [
            'items' => [
                ['product_id' => $second->id, 'quantity' => '1.250', 'purchase_price' => '24000.00'],
                ['product_id' => $first->id, 'quantity' => '5.000', 'purchase_price' => '60000.00'],
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $first->id,
            'quantity' => '15.000',
            'average_cost' => '53333.33',
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $second->id,
            'quantity' => '3.750',
            'average_cost' => '21333.33',
        ]);
        $this->assertSame('99.000', $otherStock->refresh()->quantity);
        $this->assertSame('12345.00', $otherStock->average_cost);
        $this->assertDatabaseCount('stock_receipt_items', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }
}
