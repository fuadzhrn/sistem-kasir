<?php

namespace App\Data\Dashboard;

use Carbon\CarbonImmutable;

final readonly class OwnerDashboardDateRange
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public string $period,
        public string $label,
        public int $totalDays,
        public string $granularity,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'period_label' => $this->label,
            'date_from' => $this->start->toDateString(),
            'date_to' => $this->end->toDateString(),
            'total_days' => $this->totalDays,
            'granularity' => $this->granularity,
        ];
    }
}
