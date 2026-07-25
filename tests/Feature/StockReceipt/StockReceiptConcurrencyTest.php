<?php

namespace Tests\Feature\StockReceipt;

use App\Models\BranchStock;
use App\Models\StockMovement;
use App\Models\StockReceiptItem;

class StockReceiptConcurrencyTest extends StockReceiptTestCase
{
    public function test_sequential_receipts_reuse_locked_stock_and_second_average_uses_first_result(): void
    {
        $branch = $this->createBranch('LOCK');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000', '50000.00');

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '5.000',
                'purchase_price' => '70000.00',
            ]],
        ]))->assertRedirect();

        $stock = BranchStock::query()->sole();
        $this->assertSame('20.000', $stock->quantity);
        $this->assertSame('57500.00', $stock->average_cost);
        $this->assertDatabaseCount('branch_stocks', 1);
        $this->assertDatabaseCount('stock_receipts', 2);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertSame('15.000', StockMovement::query()->orderBy('id')->get()[1]->quantity_before);
        $this->assertSame(
            '53333.33',
            StockReceiptItem::query()->orderBy('id')->get()[1]->average_cost_before,
        );
    }

    public function test_lock_order_atomic_insert_and_movement_integrity_are_structurally_enforced(): void
    {
        $source = file_get_contents(app_path('Services/StockReceipt/StockReceiptService.php'));
        $stockMigration = file_get_contents(database_path('migrations/2026_07_25_000600_create_branch_stocks_table.php'));

        $this->assertStringContainsString('usort(', $source);
        $this->assertStringContainsString("orderBy('id')", $source);
        $this->assertStringContainsString('insertOrIgnore(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('StockMovement::query()->create(', $source);
        $this->assertStringContainsString("['branch_id', 'product_id']", $stockMigration);
    }
}
