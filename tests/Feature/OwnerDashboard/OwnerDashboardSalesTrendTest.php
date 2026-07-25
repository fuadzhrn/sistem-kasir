<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Sale;

class OwnerDashboardSalesTrendTest extends OwnerDashboardTestCase
{
    public function test_daily_sales_trend_is_zero_filled_ordered_and_excludes_voided_sales(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('TRN');
        $this->createSale($branch, $owner, ['transaction_date' => '2026-07-02 09:00:00']);
        $this->createSale($branch, $owner, [
            'transaction_date' => '2026-07-03 09:00:00',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $response = $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-04',
        ])->assertOk();

        $response->assertJsonPath('data.filters.granularity', 'daily')
            ->assertJsonPath('data.charts.sales_trend.labels', ['1 Jul', '2 Jul', '3 Jul', '4 Jul'])
            ->assertJsonPath('data.charts.sales_trend.gross_sales', [0, 200000, 0, 0])
            ->assertJsonPath('data.charts.sales_trend.net_sales', [0, 180000, 0, 0]);

        foreach ($response->json('data.charts.sales_trend.net_sales') as $value) {
            $this->assertIsInt($value);
            $this->assertFalse(is_nan((float) $value));
        }
    }

    public function test_longer_ranges_use_weekly_and_monthly_granularity(): void
    {
        $owner = $this->createUser('owner');

        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-03-01',
            'date_to' => '2026-07-25',
        ])->assertJsonPath('data.filters.granularity', 'weekly');
        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-01-01',
            'date_to' => '2026-07-25',
        ])->assertJsonPath('data.filters.granularity', 'monthly');
    }
}
