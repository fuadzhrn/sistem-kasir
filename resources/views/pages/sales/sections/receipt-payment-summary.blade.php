<section class="receipt-payment">
    <dl>
        <div><dt>Subtotal</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->subtotal) }}</dd></div>
        <div><dt>Diskon</dt><dd>− {{ \App\Support\Format\Rupiah::format($sale->discount_amount) }}</dd></div>
        <div class="receipt-payment__total"><dt>Total</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->total) }}</dd></div>
        <div><dt>Metode</dt><dd>{{ $sale->payment_method_name }}</dd></div>
        <div><dt>Uang dibayar</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->amount_paid) }}</dd></div>
        <div><dt>Kembalian</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->change_amount) }}</dd></div>
    </dl>
    @if ($sale->notes)<p class="receipt-notes">Catatan: {{ $sale->notes }}</p>@endif
</section>
