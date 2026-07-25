<section class="transfer-summary-grid" aria-label="Ringkasan mutasi">
    <article class="card stat-card">
        <span class="stat-card__label">Dokumen</span>
        <strong class="stat-card__value">{{ number_format($summary['documents'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Sesuai filter aktif</span>
    </article>
    <article class="card stat-card">
        <span class="stat-card__label">Menunggu</span>
        <strong class="stat-card__value">{{ number_format($summary['pending'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Belum mengubah stok</span>
    </article>
    <article class="card stat-card">
        <span class="stat-card__label">Selesai</span>
        <strong class="stat-card__value">{{ number_format($summary['completed'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Dua movement tercatat</span>
    </article>
</section>
