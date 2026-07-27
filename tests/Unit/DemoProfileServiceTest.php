<?php

namespace Tests\Unit;

use App\Services\Demo\DemoProfileService;
use Tests\TestCase;

class DemoProfileServiceTest extends TestCase
{
    public function test_public_profiles_have_locked_target_counts(): void
    {
        $profiles = app(DemoProfileService::class);

        $this->assertSame(['small', 'medium', 'large'], $profiles->commandProfiles());
        $this->assertSame(250, $profiles->get('small')['sales']);
        $this->assertSame(2000, $profiles->get('medium')['sales']);
        $this->assertSame(5000, $profiles->get('large')['sales']);
        $this->assertSame(300, $profiles->get('large')['products']);
    }
}
