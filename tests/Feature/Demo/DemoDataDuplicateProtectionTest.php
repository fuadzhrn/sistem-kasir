<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use Illuminate\Support\Facades\Artisan;

class DemoDataDuplicateProtectionTest extends DemoDataTestCase
{
    public function test_existing_indicator_aborts_the_entire_seed(): void
    {
        Branch::query()->create([
            'code' => 'DMO1',
            'name' => 'Indikator',
            'is_active' => true,
        ]);

        $exit = Artisan::call('demo:seed', [
            '--profile' => 'testing',
            '--confirm' => 'SEED-DEMO',
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
