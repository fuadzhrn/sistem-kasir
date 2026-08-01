<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Expense;

class OwnerDashboardMobileTest extends OwnerDashboardTestCase
{
    public function test_owner_dashboard_exposes_mobile_filter_chart_fallbacks_and_card_list_labels(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('MOB');
        $product = $this->createProduct('MOB-001');
        $this->createSale($branch, $owner, [], $product);
        $this->createStock($branch, $product);
        $this->createExpense($branch, $owner, Expense::STATUS_PENDING);

        $response = $this->actingAs($owner)
            ->get(route('dashboard.owner'))
            ->assertOk()
            ->assertSeeText('Bulan Ini · Semua Cabang')
            ->assertSeeText('Atur Filter')
            ->assertSeeText('Atur Filter Dashboard')
            ->assertSee('data-dashboard-filter-open', false)
            ->assertSee('data-dashboard-filter-modal', false)
            ->assertSee('data-dashboard-filter-dialog', false)
            ->assertSee('data-dashboard-filter-overlay', false)
            ->assertSee('aria-controls="owner-dashboard-filter"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('method="GET"', false)
            ->assertSee('Grafik tren omzet dan penjualan bersih pada periode aktif.', false)
            ->assertSee('Grafik tren laba kotor dan laba bersih pada periode aktif.', false)
            ->assertSee('Grafik perbandingan penjualan bersih dan laba bersih setiap cabang.', false)
            ->assertSee('Grafik komposisi metode pembayaran pada periode aktif.', false);

        foreach ([
            'data-label="Peringkat"',
            'data-label="Penjualan Bersih"',
            'data-label="Tersedia"',
            'data-label="Minimum"',
            'data-label="Nomor Nota"',
            'data-label="Tanggal dan Waktu"',
            'data-label="Pembayaran"',
            'data-label="Total"',
            'data-label="Deskripsi"',
            'data-label="Pencatat"',
            'data-label="Jumlah"',
            'data-label="Status"',
            'dashboard-mobile-detail',
        ] as $markup) {
            $response->assertSee($markup, false);
        }

        $this->assertStringNotContainsString('onclick=', $response->getContent());
    }

    public function test_mobile_and_desktop_use_the_same_server_calculated_values(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('SAME');
        $this->createSale($branch, $owner);

        $this->actingAs($owner)
            ->get(route('dashboard.owner'))
            ->assertOk()
            ->assertSeeText('Rp200.000')
            ->assertSeeText('Rp180.000')
            ->assertSeeText('Rp120.000')
            ->assertSeeText('Rp60.000');

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonPath('data.cards.gross_sales.formatted', 'Rp200.000')
            ->assertJsonPath('data.cards.net_sales.formatted', 'Rp180.000')
            ->assertJsonPath('data.cards.cost_of_goods_sold.formatted', 'Rp120.000')
            ->assertJsonPath('data.cards.gross_profit.formatted', 'Rp60.000');
    }

    public function test_mobile_frontend_contract_uses_scoped_css_and_vanilla_javascript(): void
    {
        $css = file_get_contents(public_path('assets/css/pages/dashboard-owner.css'));
        $indexScript = file_get_contents(public_path('assets/js/pages/dashboard-owner/index.js'));
        $mobileSheetScript = file_get_contents(public_path('assets/js/components/mobile-sheet.js'));
        $chartScript = file_get_contents(public_path('assets/js/pages/dashboard-owner/dashboard-charts.js'));
        $rendererScript = file_get_contents(public_path('assets/js/pages/dashboard-owner/dashboard-renderer.js'));

        foreach (['1024px', '768px', '480px'] as $breakpoint) {
            $this->assertStringContainsString('@media (max-width: '.$breakpoint.')', $css);
        }

        $this->assertStringContainsString('.owner-dashboard .dashboard-filter-modal.is-open', $css);
        $this->assertStringContainsString('.owner-dashboard .dashboard-table-card tbody tr', $css);
        $this->assertStringContainsString('height: 340px', $css);
        $this->assertStringContainsString('height: 290px', $css);
        $this->assertStringNotContainsString('width: 100vw', $css);

        $this->assertStringContainsString('initializeMobileSheet', $indexScript);
        $this->assertStringContainsString("from '../../components/mobile-sheet.js'", $indexScript);
        $this->assertStringContainsString("event.key === 'Escape'", $mobileSheetScript);
        $this->assertStringContainsString('trapFocus(event)', $mobileSheetScript);
        $this->assertStringContainsString('filters.commit()', $indexScript);
        $this->assertStringContainsString("filterSheet.close('apply')", $indexScript);
        $this->assertStringContainsString("filterSheet.close('reset')", $indexScript);
        $this->assertStringContainsString('store-app:sidebar-changed', $indexScript);

        $this->assertStringContainsString('responsive: true', $chartScript);
        $this->assertStringContainsString('maintainAspectRatio: false', $chartScript);
        $this->assertStringContainsString('resizeDashboardCharts', $chartScript);
        $this->assertStringContainsString('maxTicksLimit', $chartScript);
        $this->assertStringContainsString('pointHitRadius: 14', $chartScript);
        $this->assertStringContainsString('formatRupiah', $chartScript);

        $this->assertStringContainsString('data.filters.period_label', $rendererScript);
        $this->assertStringContainsString('appendMobileDetail', $rendererScript);
        $this->assertStringNotContainsString('jQuery', $indexScript.$chartScript.$rendererScript);
    }
}
