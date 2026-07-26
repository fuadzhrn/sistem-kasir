<?php

namespace App\Services\Dashboard\Admin;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Services\Dashboard\OwnerDashboardInformationService;

final class AdminDashboardInformationService
{
    public function __construct(
        private readonly OwnerDashboardInformationService $informationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Branch $branch, OwnerDashboardDateRange $dateRange): array
    {
        return $this->informationService->build($branch, $dateRange);
    }
}
