<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use Illuminate\Support\Facades\Artisan;

class DemoDataEnvironmentGuardTest extends DemoDataTestCase
{
    public function test_production_environment_is_rejected_before_any_write(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $exit = Artisan::call('demo:seed', [
                '--profile' => 'small',
                '--confirm' => 'SEED-DEMO',
                '--no-interaction' => true,
            ]);
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }

        $this->assertSame(1, $exit);
        $this->assertDatabaseCount('branches', 0);
        $this->assertSame(0, Branch::query()->count());
    }
}
