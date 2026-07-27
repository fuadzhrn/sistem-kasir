<?php

namespace App\Services\Demo;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DemoDateService
{
    /**
     * @return array<int, CarbonImmutable>
     */
    public function saleDates(
        int $count,
        int $branchCount,
        int $todayPerBranch,
        int $seed,
        CarbonInterface $today,
    ): array {
        $dates = [];
        $todayCount = min($count, $branchCount * $todayPerBranch);

        for ($index = 0; $index < $todayCount; $index++) {
            $dates[] = CarbonImmutable::instance($today)
                ->startOfDay()
                ->addHours(8 + ($index % 9))
                ->addMinutes(($index * 17 + $seed) % 60);
        }

        for ($index = $todayCount; $index < $count; $index++) {
            $age = 1 + (($index * 37 + $seed) % 350);
            $seasonalShift = $index % 7 === 0 ? min(45, $age) : 0;
            $dates[] = CarbonImmutable::instance($today)
                ->subDays(max(1, $age - $seasonalShift))
                ->startOfDay()
                ->addHours(8 + (($index * 5 + $seed) % 10))
                ->addMinutes(($index * 13 + $seed) % 60);
        }

        usort($dates, static fn (CarbonInterface $left, CarbonInterface $right): int => $left <=> $right);

        return $dates;
    }

    public function historical(
        int $index,
        int $count,
        CarbonInterface $today,
        int $maximumAgeDays = 365,
    ): CarbonImmutable {
        $denominator = max(1, $count - 1);
        $age = (int) round($maximumAgeDays * (1 - ($index / $denominator)));

        return CarbonImmutable::instance($today)
            ->subDays($age)
            ->startOfDay()
            ->addHours(9 + ($index % 8))
            ->addMinutes(($index * 19) % 60);
    }
}
