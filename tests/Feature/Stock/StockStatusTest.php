<?php

namespace Tests\Feature\Stock;

use App\Services\Stock\StockService;
use Illuminate\Support\Facades\Schema;

class StockStatusTest extends StockTestCase
{
    public function test_service_calculates_out_low_and_safe_status_boundaries(): void
    {
        $service = app(StockService::class);

        $this->assertSame(StockService::STATUS_OUT, $service->calculateStockStatus('0.000', '5.000'));
        $this->assertSame(StockService::STATUS_OUT, $service->calculateStockStatus('-1.000', '5.000'));
        $this->assertSame(StockService::STATUS_LOW, $service->calculateStockStatus('1.000', '5.000'));
        $this->assertSame(StockService::STATUS_LOW, $service->calculateStockStatus('5.000', '5.000'));
        $this->assertSame(StockService::STATUS_SAFE, $service->calculateStockStatus('6.000', '5.000'));
        $this->assertSame(StockService::STATUS_SAFE, $service->calculateStockStatus('1.000', '0.000'));
    }

    public function test_status_changes_when_product_minimum_changes_without_stock_update(): void
    {
        $branch = $this->createBranch('SS01');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['minimum_stock' => '5.000', 'code' => 'STATUS-CHANGE']);
        $stock = $this->createStock($branch, $product, '6.000');
        $stockUpdatedAt = $stock->updated_at;

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'status' => 'safe']))
            ->assertSee('STATUS-CHANGE');

        $product->update(['minimum_stock' => '6.000']);

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'status' => 'low']))
            ->assertSee('STATUS-CHANGE');

        $this->assertTrue($stock->refresh()->updated_at->equalTo($stockUpdatedAt));
    }

    public function test_status_is_not_persisted_as_database_column(): void
    {
        $columns = Schema::getColumnListing('branch_stocks');

        $this->assertNotContains('status', $columns);
        $this->assertContains('quantity', $columns);
        $this->assertContains('average_cost', $columns);
    }

    public function test_out_status_filter_includes_zero_and_missing_branch_stock(): void
    {
        $branch = $this->createBranch('SS02');
        $owner = $this->createUser('owner');
        $zeroProduct = $this->createProduct(['code' => 'OUT-ZERO']);
        $missingProduct = $this->createProduct(['code' => 'OUT-MISSING']);
        $safeProduct = $this->createProduct(['code' => 'NOT-OUT']);
        $this->createStock($branch, $zeroProduct, '0.000');
        $this->createStock($branch, $safeProduct, '6.000');

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'status' => 'out']))
            ->assertOk()
            ->assertSee($zeroProduct->code)
            ->assertSee($missingProduct->code)
            ->assertDontSee($safeProduct->code);
    }
}
