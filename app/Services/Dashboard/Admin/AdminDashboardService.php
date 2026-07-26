<?php

namespace App\Services\Dashboard\Admin;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;

final class AdminDashboardService
{
    public function __construct(
        private readonly AdminDashboardFinancialService $financialService,
        private readonly AdminDashboardChartService $chartService,
        private readonly AdminDashboardInformationService $informationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Branch $branch, OwnerDashboardDateRange $dateRange): array
    {
        $information = $this->informationService->build($branch, $dateRange);
        $generatedAt = now();

        return [
            'filters' => [
                'branch_name' => $branch->name,
                ...$dateRange->toArray(),
            ],
            'cards' => $this->financialService->summarize($branch, $dateRange),
            'charts' => $this->chartService->build($branch, $dateRange),
            ...$information,
            'generated_at' => $generatedAt->toIso8601String(),
            'generated_at_formatted' => $generatedAt->translatedFormat('d F Y, H.i'),
        ];
    }
}
