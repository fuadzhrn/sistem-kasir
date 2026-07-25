<section class="card sale-summary-panel sale-internal">
    <div class="card__header">
        <div>
            <h3>Ringkasan Internal</h3>
            <p>Khusus Owner dan Admin Cabang yang berwenang.</p>
        </div>
    </div>
    <dl class="sale-money-list">
        <div><dt>Total HPP</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->total_cost) }}</dd></div>
        <div class="sale-money-list__profit"><dt>Laba kotor</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->gross_profit) }}</dd></div>
    </dl>
</section>
