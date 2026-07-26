<?php

namespace Tests\Feature\CashierDashboard;

class CashierDashboardMarkupTest extends CashierDashboardTestCase
{
    public function test_cashier_markup_has_operational_controls_without_admin_content(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, ['invoice_number' => 'MARKUP-001']);

        $response = $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertOk();

        foreach ([
            'Cari Nomor Nota',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Semua Status',
            'Detail',
            'Cetak Ulang',
            'assets/css/pages/dashboard-cashier.css',
            'assets/js/pages/dashboard-cashier.js',
        ] as $text) {
            $response->assertSee($text, false);
        }

        $response
            ->assertDontSee('name="branch_id"', false)
            ->assertDontSee('name="cashier_id"', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('chart.umd.min.js', false);
    }
}
