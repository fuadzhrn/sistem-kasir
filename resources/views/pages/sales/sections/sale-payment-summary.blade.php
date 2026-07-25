<section class="card sale-summary-panel">
    <div class="card__header"><h3>Ringkasan Pembayaran</h3></div>
    <dl class="sale-money-list">
        <div><dt>Subtotal</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->subtotal) }}</dd></div>
        <div><dt>Diskon</dt><dd>− {{ \App\Support\Format\Rupiah::format($sale->discount_amount) }}</dd></div>
        <div class="sale-money-list__total"><dt>Total</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->total) }}</dd></div>
        <div><dt>Uang dibayar</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->amount_paid) }}</dd></div>
        <div><dt>Kembalian</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->change_amount) }}</dd></div>
    </dl>
</section>
