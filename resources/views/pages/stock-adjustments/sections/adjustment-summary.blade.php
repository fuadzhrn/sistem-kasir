<section class="adjustment-summary-grid" aria-label="Ringkasan penyesuaian">
    <article class="card stat-card">
        <span class="stat-card__label">Dokumen</span>
        <strong class="stat-card__value">{{ number_format($summary['documents'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Sesuai filter aktif</span>
    </article>
    <article class="card stat-card">
        <span class="stat-card__label">Penyesuaian masuk</span>
        <strong class="stat-card__value">{{ number_format($summary['increases'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Perubahan quantity positif</span>
    </article>
    <article class="card stat-card">
        <span class="stat-card__label">Penyesuaian keluar</span>
        <strong class="stat-card__value">{{ number_format($summary['decreases'], 0, ',', '.') }}</strong>
        <span class="stat-card__meta">Perubahan quantity negatif</span>
    </article>
</section>
