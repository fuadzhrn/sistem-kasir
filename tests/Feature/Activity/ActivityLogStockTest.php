<?php

namespace Tests\Feature\Activity;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Stock\StockService;

class ActivityLogStockTest extends ActivityLogTestCase
{
    public function test_initial_stock_is_audited_in_target_branch(): void
    {
        $branch = $this->branch();
        $owner = $this->user('owner');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create(['is_active' => true]),
            'unit_id' => Unit::factory()->create(['is_active' => true]),
            'purchase_price' => '10000.00',
            'is_active' => true,
        ]);

        $this->actingAs($owner);
        app(StockService::class)->setInitialStock($branch, $product, '10.000', 'Stok pembukaan toko', $owner);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'initial_stock_created',
            'module' => 'stocks',
            'branch_id' => $branch->id,
        ]);
    }
}
