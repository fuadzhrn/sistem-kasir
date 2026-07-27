<?php

namespace Tests\Feature\Demo;

use App\Models\Sale;
use App\Models\User;

class DemoDataDashboardTest extends DemoDataTestCase
{
    public function test_each_branch_has_completed_sales_today_and_historical_trend_data(): void
    {
        $this->seedDemo();

        $this->assertSame(
            4,
            Sale::query()->whereDate('transaction_date', today())->where('status', 'completed')->distinct()->count('branch_id'),
        );
        $this->assertGreaterThan(1, Sale::query()->selectRaw("strftime('%Y-%m', transaction_date) period")->distinct()->count());
        $this->assertGreaterThan(0, (float) Sale::query()->where('status', 'completed')->sum('total'));

        $owner = User::query()->where('username', 'demo_owner')->firstOrFail();
        $admin = User::query()->where('username', 'demo_admin_dmo1')->firstOrFail();
        $cashier = User::query()->where('username', 'demo_kasir_dmo1_01')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard.owner'))
            ->assertOk()
            ->assertSee('Dashboard Owner');
        $this->get(route('dashboard.owner.data'))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Dashboard Cabang');
        $this->get(route('dashboard.admin.data'))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertOk()
            ->assertSee('Dashboard Kasir');
    }
}
