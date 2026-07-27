<?php

namespace Tests\Feature\Demo;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DemoDataDryRunTest extends DemoDataTestCase
{
    public function test_dry_run_has_zero_database_and_file_writes(): void
    {
        Storage::fake('local');

        $exit = Artisan::call('demo:seed', [
            '--profile' => 'small',
            '--seed' => '20260726',
            '--dry-run' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseCount('branches', 0);
        Storage::disk('local')->assertMissing('demo/demo-seed-manifest.json');
        $this->assertStringContainsString('DRY RUN selesai', Artisan::output());
    }
}
