<?php

namespace Tests\Unit;

use App\Services\Dashboard\OwnerDashboardDateRangeService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class OwnerDashboardDateRangeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_period_boundaries_follow_application_timezone_and_monday_week_start(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-07-25 14:35:00', 'Asia/Jakarta'),
        );
        $service = app(OwnerDashboardDateRangeService::class);

        $today = $service->resolve('today');
        $week = $service->resolve('this_week');
        $month = $service->resolve('this_month');
        $year = $service->resolve('this_year');

        $this->assertSame('2026-07-25 00:00:00', $today->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-20', $week->start->toDateString());
        $this->assertSame('2026-07-01', $month->start->toDateString());
        $this->assertSame('2026-01-01', $year->start->toDateString());
        $this->assertSame('Asia/Jakarta', $today->start->timezoneName);
    }

    public function test_custom_ranges_choose_daily_weekly_and_monthly_granularity(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-07-25 14:35:00', 'Asia/Jakarta'),
        );
        $service = app(OwnerDashboardDateRangeService::class);

        $this->assertSame('daily', $service->resolve('custom', '2026-07-01', '2026-07-25')->granularity);
        $this->assertSame('weekly', $service->resolve('custom', '2026-03-01', '2026-07-25')->granularity);
        $this->assertSame('monthly', $service->resolve('custom', '2026-01-01', '2026-07-25')->granularity);
        $this->assertSame('2026-07-25 14:35:00', $service
            ->resolve('custom', '2026-07-01', '2026-07-25')
            ->end
            ->format('Y-m-d H:i:s'));
    }
}
