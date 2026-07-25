<?php

namespace App\Services\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use Carbon\CarbonImmutable;

final class OwnerDashboardDateRangeService
{
    public function resolve(
        string $period,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): OwnerDashboardDateRange {
        $now = CarbonImmutable::now(config('app.timezone'));

        [$start, $end, $label] = match ($period) {
            'today' => [$now->startOfDay(), $now, 'Hari Ini'],
            'this_week' => [$now->startOfWeek(CarbonImmutable::MONDAY), $now, 'Minggu Ini'],
            'this_year' => [$now->startOfYear(), $now, 'Tahun Ini'],
            'custom' => $this->customRange($now, $dateFrom, $dateTo),
            default => [$now->startOfMonth(), $now, 'Bulan Ini'],
        };

        $totalDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return new OwnerDashboardDateRange(
            start: $start,
            end: $end,
            period: $period,
            label: $label,
            totalDays: $totalDays,
            granularity: match (true) {
                $totalDays <= 31 => 'daily',
                $totalDays <= 180 => 'weekly',
                default => 'monthly',
            },
        );
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable, string}
     */
    private function customRange(
        CarbonImmutable $now,
        ?string $dateFrom,
        ?string $dateTo,
    ): array {
        $start = CarbonImmutable::parse((string) $dateFrom, config('app.timezone'))->startOfDay();
        $requestedEnd = CarbonImmutable::parse((string) $dateTo, config('app.timezone'))->endOfDay();
        $end = $requestedEnd->isSameDay($now) ? $now : $requestedEnd;

        return [
            $start,
            $end,
            $start->translatedFormat('j M Y').' – '.$end->translatedFormat('j M Y'),
        ];
    }
}
