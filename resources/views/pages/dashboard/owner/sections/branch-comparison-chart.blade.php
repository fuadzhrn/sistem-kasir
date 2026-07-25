<article class="dashboard-chart-card card" data-chart-card="branch_comparison">
    <header>
        <h2>Perbandingan Cabang</h2>
        <p data-branch-chart-subtitle>Penjualan bersih dan laba bersih menurut cabang.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="branch_comparison" aria-label="Grafik perbandingan cabang" role="img"></canvas>
    </div>
    @include('pages.dashboard.owner.sections.dashboard-empty-state', ['message' => 'Belum ada cabang untuk dibandingkan.', 'key' => 'branch_comparison'])
</article>
