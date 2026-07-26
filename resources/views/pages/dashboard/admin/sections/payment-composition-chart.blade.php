<article class="dashboard-chart-card card" data-chart-card="payment_composition">
    <header>
        <h2>Komposisi Metode Pembayaran</h2>
        <p>Persentase penjualan bersih cabang berdasarkan snapshot metode pembayaran.</p>
    </header>
    <div class="dashboard-chart-card__canvas">
        <canvas data-dashboard-chart="payment_composition" aria-label="Grafik komposisi metode pembayaran cabang" role="img"></canvas>
    </div>
    @include('pages.dashboard.admin.sections.dashboard-empty-state', ['message' => 'Belum ada pembayaran pada periode ini.', 'key' => 'payment_composition'])
</article>
