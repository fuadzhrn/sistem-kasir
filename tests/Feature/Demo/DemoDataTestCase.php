<?php

namespace Tests\Feature\Demo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

abstract class DemoDataTestCase extends TestCase
{
    use RefreshDatabase;

    protected function seedDemo(): void
    {
        putenv('DEMO_USER_PASSWORD=DemoTesting-Only-2026!');

        try {
            $exit = Artisan::call('demo:seed', [
                '--profile' => 'testing',
                '--seed' => '20260726',
                '--confirm' => 'SEED-DEMO',
                '--no-interaction' => true,
            ]);
        } finally {
            putenv('DEMO_USER_PASSWORD');
        }

        $this->assertSame(0, $exit, Artisan::output());
    }
}
