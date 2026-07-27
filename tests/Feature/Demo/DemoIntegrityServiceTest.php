<?php

namespace Tests\Feature\Demo;

use App\Services\Demo\DemoIntegrityService;

class DemoIntegrityServiceTest extends DemoDataTestCase
{
    public function test_integrity_service_returns_only_pass_results_for_testing_profile(): void
    {
        $this->seedDemo();

        $result = app(DemoIntegrityService::class)->verify();

        $this->assertNotEmpty($result['passes']);
        $this->assertSame([], $result['warnings']);
        $this->assertSame([], $result['failures']);
    }
}
