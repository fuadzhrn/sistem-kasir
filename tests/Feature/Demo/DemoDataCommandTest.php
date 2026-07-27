<?php

namespace Tests\Feature\Demo;

use Illuminate\Support\Facades\Artisan;

class DemoDataCommandTest extends DemoDataTestCase
{
    public function test_confirmation_token_is_required_for_actual_seed(): void
    {
        $exit = Artisan::call('demo:seed', [
            '--profile' => 'testing',
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Token konfirmasi tidak valid', Artisan::output());
        $this->assertDatabaseCount('branches', 0);
    }
}
