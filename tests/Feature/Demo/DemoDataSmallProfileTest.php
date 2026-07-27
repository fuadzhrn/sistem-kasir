<?php

namespace Tests\Feature\Demo;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoDataSmallProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_profile_builds_an_integrated_demo_dataset(): void
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
        $this->assertSame(4, Branch::query()->where('code', 'like', 'DMO%')->count());
        $this->assertSame(12, Product::query()->where('code', 'like', 'DMO-%')->count());
        $this->assertSame(13, User::query()->where('username', 'like', 'demo_%')->count());
        $this->assertSame(20, Sale::query()->count());
        $this->assertSame(0, Artisan::call('demo:verify', ['--strict' => true]), Artisan::output());

        $owner = User::query()->where('username', 'demo_owner')->firstOrFail();
        $sale = Sale::query()->firstOrFail();
        $this->actingAs($owner)->get(route('products.index'))->assertOk();
        $this->get(route('stocks.index'))->assertOk();
        $this->get(route('sales.index'))->assertOk();
        $this->get(route('expenses.index'))->assertOk();
        $this->get(route('activities.index'))->assertOk();
        $this->get(route('receipts.print', $sale))->assertOk();
    }
}
