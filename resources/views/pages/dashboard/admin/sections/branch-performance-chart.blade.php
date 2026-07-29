<article class="dashboard-chart-card card" data-chart-card="branch_performance">
    <header>
        <h2>Penjualan dan Laba Cabang</h2>
        <p>Penjualan bersih dan laba bersih cabang pada setiap periode.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="branch_performance" aria-label="Grafik penjualan dan laba cabang" role="img">
            Grafik penjualan bersih dan laba bersih cabang pada periode aktif.
        </canvas>
    </div>
    @include('pages.dashboard.admin.sections.dashboard-empty-state', ['message' => 'Belum ada kinerja cabang pada periode ini.', 'key' => 'branch_performance'])
</article>
