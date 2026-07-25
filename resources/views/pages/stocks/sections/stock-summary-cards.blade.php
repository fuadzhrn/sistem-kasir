<section class="stock-summary-grid" aria-label="Ringkasan status stok cabang">
    <article class="card stock-summary-card">
        <span>SKU Aktif</span>
        <strong>{{ $stockSummary['total'] }}</strong>
    </article>
    <article class="card stock-summary-card stock-summary-card--safe">
        <span>Aman</span>
        <strong>{{ $stockSummary['safe'] }}</strong>
    </article>
    <article class="card stock-summary-card stock-summary-card--low">
        <span>Menipis</span>
        <strong>{{ $stockSummary['low'] }}</strong>
    </article>
    <article class="card stock-summary-card stock-summary-card--out">
        <span>Habis</span>
        <strong>{{ $stockSummary['out'] }}</strong>
    </article>
</section>
