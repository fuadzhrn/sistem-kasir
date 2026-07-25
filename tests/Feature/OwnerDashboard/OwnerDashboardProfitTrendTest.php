<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Expense;

class OwnerDashboardProfitTrendTest extends OwnerDashboardTestCase
{
    public function test_profit_trend_uses_transaction_and_expense_dates_and_preserves_negative_values(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('PFT');
        $this->createSale($branch, $owner, ['transaction_date' => '2026-07-02 10:00:00']);
        $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, [
            'expense_date' => '2026-07-03',
            'amount' => '80000.00',
            'created_at' => '2026-07-01 09:00:00',
        ]);
        $this->createExpense($branch, $owner, Expense::STATUS_PENDING, [
            'expense_date' => '2026-07-03',
            'amount' => '900000.00',
        ]);

        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-04',
        ])
            ->assertOk()
            ->assertJsonPath('data.charts.profit_trend.gross_profit', [0, 60000, 0, 0])
            ->assertJsonPath('data.charts.profit_trend.net_profit', [0, 60000, -80000, 0]);
    }
}
