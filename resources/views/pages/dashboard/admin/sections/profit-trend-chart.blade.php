<article class="dashboard-chart-card card" data-chart-card="profit_trend">
    <header>
        <h2>Tren Laba</h2>
        <p>Laba kotor dan laba bersih cabang setelah pengeluaran disetujui.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="profit_trend" aria-label="Grafik tren laba cabang" role="img">
            Grafik laba kotor dan laba bersih untuk cabang aktif.
        </canvas>
    </div>
    @include('pages.dashboard.admin.sections.dashboard-empty-state', ['message' => 'Belum ada data laba pada periode ini.', 'key' => 'profit_trend'])
</article>
