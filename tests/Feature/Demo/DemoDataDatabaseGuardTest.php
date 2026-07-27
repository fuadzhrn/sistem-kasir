<?php

namespace Tests\Feature\Demo;

use Illuminate\Support\Facades\Artisan;

class DemoDataDatabaseGuardTest extends DemoDataTestCase
{
    public function test_non_demo_database_is_rejected_without_local_override(): void
    {
        config()->set('database.connections.sqlite.database', 'sistem_toko.sqlite');

        $exit = Artisan::call('demo:seed', [
            '--profile' => 'small',
            '--confirm' => 'SEED-DEMO',
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertDatabaseCount('branches', 0);
    }
}
