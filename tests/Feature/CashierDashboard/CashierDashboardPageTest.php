<?php

namespace Tests\Feature\CashierDashboard;

class CashierDashboardPageTest extends CashierDashboardTestCase
{
    public function test_cashier_can_open_simple_personal_dashboard(): void
    {
        $branch = $this->createBranch('KSR');
        $cashier = $this->createUser('cashier', $branch, ['name' => 'Kasir Uji']);

        $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertOk()
            ->assertSee('Dashboard Kasir')
            ->assertSee('Kasir Uji')
            ->assertSee($branch->name)
            ->assertSee('Transaksi Baru')
            ->assertSee(route('cashier.index'), false)
            ->assertSee('Ringkasan Hari Ini')
            ->assertSee('Riwayat Transaksi Terbaru')
            ->assertDontSee('Chart.js');
    }
}
