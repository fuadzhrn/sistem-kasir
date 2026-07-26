<?php

namespace Tests\Feature\AdminDashboard;

use App\Models\Expense;

class AdminDashboardChartsTest extends AdminDashboardTestCase
{
    public function test_admin_charts_are_scoped_and_include_empty_period_buckets(): void
    {
        $branch = $this->createBranch('CHA');
        $otherBranch = $this->createBranch('CHB', ['name' => 'Cabang Grafik Rahasia']);
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $otherCashier = $this->createUser('cashier', $otherBranch);
        $this->createSale($branch, $cashier, [
            'transaction_date' => '2026-07-10 10:00:00',
            'payment_method_name' => 'Tunai',
        ]);
        $this->createSale($otherBranch, $otherCashier, [
            'transaction_date' => '2026-07-10 10:00:00',
            'payment_method_name' => 'Rahasia',
        ]);
        $this->createExpense($branch, $admin, Expense::STATUS_APPROVED);

        $response = $this->getAdminData($admin)->assertOk();
        $response->assertJsonStructure([
            'data' => ['charts' => [
                'sales_trend' => ['labels', 'gross_sales', 'net_sales', 'empty'],
                'profit_trend' => ['labels', 'gross_profit', 'net_profit', 'empty'],
                'branch_performance' => ['labels', 'net_sales', 'net_profit', 'empty'],
                'payment_composition' => ['labels', 'values', 'percentages', 'empty'],
            ]],
        ]);
        $response->assertJsonMissingPath('data.charts.branch_comparison');
        $response->assertDontSee('Cabang Grafik Rahasia');
        $response->assertDontSee('Rahasia');
        $this->assertCount(25, $response->json('data.charts.sales_trend.labels'));
        $this->assertSame(
            $response->json('data.charts.sales_trend.labels'),
            $response->json('data.charts.branch_performance.labels'),
        );
    }
}
