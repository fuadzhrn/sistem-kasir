<section class="sales-summary" aria-label="Ringkasan hasil filter">
    <article class="card sales-summary__card">
        <span>Jumlah transaksi</span>
        <strong>{{ number_format($summary['transaction_count'], 0, ',', '.') }}</strong>
    </article>
    <article class="card sales-summary__card">
        <span>Total penjualan bersih</span>
        <strong>{{ \App\Support\Format\Rupiah::format($summary['net_total']) }}</strong>
    </article>
    <article class="card sales-summary__card">
        <span>Total diskon</span>
        <strong>{{ \App\Support\Format\Rupiah::format($summary['discount_total']) }}</strong>
    </article>
    <article class="card sales-summary__card sales-summary__statuses">
        <span>Status transaksi</span>
        <div>
            <small>Selesai <strong>{{ $summary['completed_count'] }}</strong></small>
            <small>Menunggu <strong>{{ $summary['void_requested_count'] }}</strong></small>
            <small>Dibatalkan <strong>{{ $summary['voided_count'] }}</strong></small>
        </div>
    </article>
</section>
