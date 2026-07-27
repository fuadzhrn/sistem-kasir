<?php

namespace Tests\Unit;

use App\Services\Demo\DemoDateService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DemoDateServiceTest extends TestCase
{
    public function test_dates_are_deterministic_sorted_and_cover_today(): void
    {
        $service = app(DemoDateService::class);
        $today = CarbonImmutable::parse('2026-07-26', 'Asia/Jakarta');
        $first = $service->saleDates(50, 4, 3, 20260726, $today);
        $second = $service->saleDates(50, 4, 3, 20260726, $today);

        $this->assertSame(
            array_map(fn ($date): string => $date->toIso8601String(), $first),
            array_map(fn ($date): string => $date->toIso8601String(), $second),
        );
        $this->assertSame($first, collect($first)->sort()->values()->all());
        $this->assertCount(12, collect($first)->filter(fn ($date): bool => $date->isSameDay($today)));
    }
}
