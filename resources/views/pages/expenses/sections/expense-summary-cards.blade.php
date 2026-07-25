<section class="summary-grid expense-summary" aria-label="Ringkasan pengeluaran">
    <article class="card summary-card">
        <span>Menunggu</span>
        <strong>{{ number_format($summary['pending'], 0, ',', '.') }}</strong>
        <small>{{ \App\Support\Format\Rupiah::format($summary['pending_total']) }}</small>
    </article>
    <article class="card summary-card">
        <span>Disetujui</span>
        <strong>{{ number_format($summary['approved'], 0, ',', '.') }}</strong>
        <small>{{ \App\Support\Format\Rupiah::format($summary['approved_total']) }}</small>
    </article>
    <article class="card summary-card">
        <span>Ditolak</span>
        <strong>{{ number_format($summary['rejected'], 0, ',', '.') }}</strong>
        <small>Tidak dihitung sebagai biaya</small>
    </article>
</section>
