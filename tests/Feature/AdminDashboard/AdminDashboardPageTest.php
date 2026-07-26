<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardPageTest extends AdminDashboardTestCase
{
    public function test_admin_can_open_branch_dashboard(): void
    {
        $branch = $this->createBranch('ADM');
        $admin = $this->createUser('admin', $branch, ['name' => 'Admin Cabang']);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Dashboard Cabang')
            ->assertSee($branch->name)
            ->assertSee('Data dashboard dibatasi untuk Cabang')
            ->assertSee('Omzet')
            ->assertSee('Penjualan Bersih')
            ->assertSee('HPP')
            ->assertSee('Laba Bersih');
    }
}
