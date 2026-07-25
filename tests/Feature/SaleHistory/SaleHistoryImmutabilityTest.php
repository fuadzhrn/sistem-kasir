<?php

namespace Tests\Feature\SaleHistory;

use App\Models\ActivityLog;
use App\Models\BranchStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

class SaleHistoryImmutabilityTest extends SaleHistoryTestCase
{
    public function test_stage_fourteen_history_routes_remain_read_only_without_crud_mutations(): void
    {
        foreach (['sales.edit', 'sales.update', 'sales.destroy'] as $routeName) {
            $this->assertFalse(Route::has($routeName));
        }

        foreach (['sales.index', 'sales.show', 'sales.receipt.show'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertSame(['GET', 'HEAD'], $route->methods());
        }
    }

    public function test_index_detail_and_preview_do_not_change_historical_or_stock_data(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');
        $before = $sale->fresh()->getRawOriginal();
        $counts = [
            Sale::class => Sale::query()->count(),
            SaleItem::class => SaleItem::query()->count(),
            BranchStock::class => BranchStock::query()->count(),
            StockMovement::class => StockMovement::query()->count(),
            ActivityLog::class => ActivityLog::query()->count(),
        ];

        $this->actingAs($owner)->get(route('sales.index', ['search' => 'AAA']))->assertOk();
        $this->get(route('sales.show', $sale))->assertOk();
        $this->get(route('sales.receipt.show', $sale))->assertOk();

        $this->assertSame($before, $sale->fresh()->getRawOriginal());
        foreach ($counts as $model => $count) {
            $this->assertSame($count, $model::query()->count());
        }
    }
}
