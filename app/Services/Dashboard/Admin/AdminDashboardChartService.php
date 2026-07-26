<?php

namespace App\Services\Dashboard\Admin;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Services\Dashboard\OwnerDashboardChartService;

final class AdminDashboardChartService
{
    public function __construct(
        private readonly OwnerDashboardChartService $chartService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Branch $branch, OwnerDashboardDateRange $dateRange): array
    {
        $charts = $this->chartService->build($branch, $dateRange, false);
        $salesTrend = $charts['sales_trend'];
        $profitTrend = $charts['profit_trend'];

        $charts['branch_performance'] = [
            'labels' => $salesTrend['labels'],
            'net_sales' => $salesTrend['net_sales'],
            'net_profit' => $profitTrend['net_profit'],
            'empty' => $salesTrend['empty'] && $profitTrend['empty'],
        ];

        return $charts;
    }
}
