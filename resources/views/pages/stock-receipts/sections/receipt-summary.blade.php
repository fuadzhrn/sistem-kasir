<section class="receipt-summary-grid" aria-label="Ringkasan hasil filter">
    <article class="card stat-card">
        <div class="stat-card__top"><span class="stat-card__label">Dokumen</span></div>
        <strong class="stat-card__value">{{ number_format($summary['documents'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Penerimaan sesuai filter</span>
    </article>
    <article class="card stat-card">
        <div class="stat-card__top"><span class="stat-card__label">Jenis produk tercatat</span></div>
        <strong class="stat-card__value">{{ number_format($summary['products'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Jumlah baris item, bukan quantity lintas satuan</span>
    </article>
    <article class="card stat-card">
        <div class="stat-card__top"><span class="stat-card__label">Total biaya dokumen</span></div>
        <strong class="stat-card__value receipt-summary__money">{{ \App\Support\Format\Rupiah::format($summary['total_cost']) }}</strong>
        <span class="stat-card__meta">Dihitung dari dokumen sesuai filter</span>
    </article>
</section>
