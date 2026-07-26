<?php

namespace App\Services\Report;

use Carbon\CarbonImmutable;

final class ReportDateRangeService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function resolve(array $filters): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $period = (string) ($filters['period'] ?? 'this_month');

        [$start, $end, $label] = match ($period) {
            'today' => [$now->startOfDay(), $now, 'Hari Ini'],
            'this_week' => [$now->startOfWeek(CarbonImmutable::MONDAY), $now, 'Minggu Ini'],
            'this_year' => [$now->startOfYear(), $now, 'Tahun Ini'],
            'custom' => [
                CarbonImmutable::parse((string) $filters['date_from'], config('app.timezone'))->startOfDay(),
                CarbonImmutable::parse((string) $filters['date_to'], config('app.timezone'))->endOfDay(),
                CarbonImmutable::parse((string) $filters['date_from'])->translatedFormat('d M Y')
                    .' – '.CarbonImmutable::parse((string) $filters['date_to'])->translatedFormat('d M Y'),
            ],
            default => [$now->startOfMonth(), $now, 'Bulan Ini'],
        };

        $days = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'label' => $label,
            'granularity' => $filters['granularity'] ?? match (true) {
                $days <= 31 => 'daily',
                $days <= 180 => 'weekly',
                default => 'monthly',
            },
        ];
    }
}
