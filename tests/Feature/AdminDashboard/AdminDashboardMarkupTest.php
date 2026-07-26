<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardMarkupTest extends AdminDashboardTestCase
{
    public function test_admin_markup_contains_required_sections_and_local_assets(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);

        $response = $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk();

        foreach ([
            'Tren Penjualan',
            'Tren Laba',
            'Penjualan dan Laba Cabang',
            'Komposisi Metode Pembayaran',
            'Produk Terlaris',
            'Stok Hampir Habis',
            'Transaksi Terbaru',
            'Pengeluaran Terbaru',
            'Memuat data dashboard',
            'Data belum dapat diperbarui',
        ] as $text) {
            $response->assertSee($text);
        }

        $response
            ->assertSee('assets/vendor/chartjs/chart.umd.min.js', false)
            ->assertSee('assets/js/pages/dashboard-admin/index.js', false)
            ->assertSee('assets/css/pages/dashboard-admin.css', false)
            ->assertDontSee('name="branch_id"', false)
            ->assertDontSee('cdn.jsdelivr.net', false)
            ->assertDontSee('onclick=', false);
    }
}
