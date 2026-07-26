<?php

namespace App\Services\Dashboard\Admin;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Services\Dashboard\OwnerDashboardFinancialService;

final class AdminDashboardFinancialService
{
    public function __construct(
        private readonly OwnerDashboardFinancialService $financialService,
    ) {}

    /**
     * @return array<string, array{value: int|string, formatted: string}>
     */
    public function summarize(Branch $branch, OwnerDashboardDateRange $dateRange): array
    {
        return $this->financialService->summarize($branch, $dateRange);
    }
}
