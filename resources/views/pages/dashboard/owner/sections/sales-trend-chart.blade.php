<article class="dashboard-chart-card card" data-chart-card="sales_trend">
    <header>
        <h2>Tren Penjualan</h2>
        <p>Omzet dan penjualan bersih per periode.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="sales_trend" aria-label="Grafik tren penjualan" role="img">
            Grafik tren omzet dan penjualan bersih pada periode aktif.
        </canvas>
    </div>
    @include('pages.dashboard.owner.sections.dashboard-empty-state', ['message' => 'Belum ada penjualan pada periode ini.', 'key' => 'sales_trend'])
</article>
