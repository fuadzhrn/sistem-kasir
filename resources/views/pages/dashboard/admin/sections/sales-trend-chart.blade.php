<article class="dashboard-chart-card card" data-chart-card="sales_trend">
    <header>
        <h2>Tren Penjualan</h2>
        <p>Omzet dan penjualan bersih cabang per periode.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="sales_trend" aria-label="Grafik tren penjualan cabang" role="img"></canvas>
    </div>
    @include('pages.dashboard.admin.sections.dashboard-empty-state', ['message' => 'Belum ada penjualan pada periode ini.', 'key' => 'sales_trend'])
</article>
