<?php

namespace Tests\Feature\Receipt;

use App\Models\ActivityLog;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;

class ReceiptPrintImmutabilityTest extends ReceiptPrintTestCase
{
    public function test_opening_and_reprinting_receipt_never_changes_transaction_or_stock(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
        $stock = BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '9.500',
            'average_cost' => '12000.00',
        ]);
        $saleBefore = $sale->fresh()->getRawOriginal();
        $stockBefore = $stock->fresh()->getRawOriginal();
        $counts = [
            Sale::class => Sale::query()->count(),
            SaleItem::class => SaleItem::query()->count(),
            StockMovement::class => StockMovement::query()->count(),
            ActivityLog::class => ActivityLog::query()->where('action', 'sale_created')->count(),
        ];

        $this->actingAs($owner)->get($this->printUrl($sale->id))->assertOk();
        $this->get($this->printUrl($sale->id, ['copy' => 1]))->assertOk();
        $this->get($this->printUrl($sale->id, ['copy' => 1, 'paper' => '58']))->assertOk();

        $this->assertSame($saleBefore, $sale->fresh()->getRawOriginal());
        $this->assertSame($stockBefore, $stock->fresh()->getRawOriginal());

        foreach ($counts as $model => $count) {
            $query = $model::query();

            if ($model === ActivityLog::class) {
                $query->where('action', 'sale_created');
            }

            $this->assertSame($count, $query->count());
        }
    }
}
