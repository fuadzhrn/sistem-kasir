<?php

namespace App\Services\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;

final class OwnerDashboardService
{
    public function __construct(
        private readonly OwnerDashboardFinancialService $financialService,
        private readonly OwnerDashboardChartService $chartService,
        private readonly OwnerDashboardInformationService $informationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): array {
        $information = $this->informationService->build($branch, $dateRange);

        return [
            'filters' => [
                'branch_id' => $branch?->getKey(),
                'branch_name' => $branch?->name ?? 'Semua Cabang',
                ...$dateRange->toArray(),
            ],
            'cards' => $this->financialService->summarize($branch, $dateRange),
            'charts' => $this->chartService->build($branch, $dateRange),
            ...$information,
            'generated_at' => now()->toIso8601String(),
            'generated_at_formatted' => now()->translatedFormat('d F Y, H.i'),
        ];
    }
}
