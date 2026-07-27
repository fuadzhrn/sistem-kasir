<?php

namespace Tests\Unit;

use App\Services\Demo\DemoDateService;
use App\Services\Demo\DemoProfileService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DemoDataDeterministicTest extends TestCase
{
    public function test_same_seed_and_profile_produce_the_same_plan(): void
    {
        $profiles = app(DemoProfileService::class);
        $dates = app(DemoDateService::class);
        $today = CarbonImmutable::parse('2026-07-26');

        $this->assertSame($profiles->get('small'), $profiles->get('small'));
        $this->assertEquals(
            $dates->saleDates(250, 4, 3, 20260726, $today),
            $dates->saleDates(250, 4, 3, 20260726, $today),
        );
    }
}
