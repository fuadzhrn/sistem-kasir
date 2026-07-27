<?php

namespace Tests\Feature\Demo;

use App\Models\Expense;
use App\Models\PriceHistory;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\User;

class DemoDataReportTest extends DemoDataTestCase
{
    public function test_report_source_tables_have_demo_data(): void
    {
        $this->seedDemo();

        $this->assertTrue(Sale::query()->exists());
        $this->assertTrue(Expense::query()->exists());
        $this->assertTrue(StockMovement::query()->exists());
        $this->assertTrue(StockReceipt::query()->exists());
        $this->assertTrue(PriceHistory::query()->exists());

        $owner = User::query()->where('username', 'demo_owner')->firstOrFail();
        $reportRoutes = [
            'reports.sales.index',
            'reports.receipts.index',
            'reports.sale-voids.index',
            'reports.stocks.index',
            'reports.stock-receipts.index',
            'reports.stock-movements.index',
            'reports.expenses.index',
            'reports.cost-of-goods-sold.index',
            'reports.gross-profit.index',
            'reports.net-profit.index',
            'reports.top-products.index',
            'reports.cashiers.index',
            'reports.branches.index',
            'reports.price-histories.index',
        ];

        foreach ($reportRoutes as $route) {
            $this->actingAs($owner)->get(route($route))->assertOk();
        }
    }
}
