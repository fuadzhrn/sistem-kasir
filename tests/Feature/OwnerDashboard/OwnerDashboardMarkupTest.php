<?php

namespace Tests\Feature\OwnerDashboard;

class OwnerDashboardMarkupTest extends OwnerDashboardTestCase
{
    public function test_owner_dashboard_markup_contains_filters_cards_charts_states_and_local_assets(): void
    {
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)->get(route('dashboard.owner'))->assertOk();

        foreach ([
            'Dashboard Owner',
            'Semua Cabang',
            'Hari Ini',
            'Minggu Ini',
            'Bulan Ini',
            'Tahun Ini',
            'Rentang Tanggal',
            'Omzet',
            'Penjualan Bersih',
            'HPP',
            'Laba Kotor',
            'Pengeluaran',
            'Laba Bersih',
            'Jumlah Nota',
            'Tren Penjualan',
            'Tren Laba',
            'Perbandingan Cabang',
            'Komposisi Metode Pembayaran',
            'Produk Terlaris',
            'Stok Hampir Habis',
            'Transaksi Terbaru',
            'Pengeluaran Terbaru',
            'Perbarui Data',
            'Coba Lagi',
            'aria-live',
        ] as $text) {
            $response->assertSee($text, false);
        }

        $response
            ->assertSee('assets/vendor/chartjs/chart.umd.min.js', false)
            ->assertSee('assets/js/pages/dashboard-owner/index.js', false)
            ->assertSee('data-dashboard-loading', false)
            ->assertSee('data-dashboard-error', false)
            ->assertSee('data-chart-empty', false)
            ->assertDontSee('cdn.jsdelivr.net', false)
            ->assertDontSee('onclick=', false);
    }

    public function test_sidebar_links_dashboard_only_for_owner(): void
    {
        $branch = $this->createBranch('NAV');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($owner)->get(route('account.index'))
            ->assertSee(route('dashboard.owner'), false);
        $this->actingAs($admin)->get(route('account.index'))
            ->assertDontSee(route('dashboard.owner'), false);
        $this->actingAs($cashier)->get(route('account.index'))
            ->assertDontSee(route('dashboard.owner'), false);
    }
}
