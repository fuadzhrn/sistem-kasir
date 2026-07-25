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
    public function test_stage_fourteen_exposes_only_get_routes_without_crud_mutations(): void
    {
        foreach (['sales.edit', 'sales.update', 'sales.destroy', 'sales.void'] as $routeName) {
            $this->assertFalse(Route::has($routeName));
        }

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'sales.'));

        $this->assertNotEmpty($routes);
        $routes->each(fn ($route) => $this->assertSame(['GET', 'HEAD'], $route->methods()));
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
