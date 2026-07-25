@php
    $defaultPayment = $paymentMethods->firstWhere('code', 'CASH') ?? $paymentMethods->first();
@endphp
<section class="cashier-payment" aria-labelledby="cashier-payment-heading">
    <h3 id="cashier-payment-heading">Pembayaran</h3>
    @if ($paymentMethods->isEmpty())
        <div class="alert alert-warning" role="alert">Belum ada metode pembayaran aktif.</div>
    @endif
    <div class="cashier-payment__grid">
        <div class="form-group">
            <label class="form-label" for="cashier-discount">Diskon</label>
            <input class="form-control" id="cashier-discount" type="number" min="0" step="1" value="0" inputmode="numeric" data-payment-discount>
            <span class="form-help">
                @if ($discountRestricted)
                    Batas akun: {{ \App\Support\Format\Rupiah::format($maximumDiscount) }}
                @else
                    Maksimal sebesar subtotal transaksi.
                @endif
            </span>
        </div>
        <div class="form-group">
            <label class="form-label" for="cashier-payment-method">Metode pembayaran</label>
            <select class="form-select" id="cashier-payment-method" data-payment-method @disabled($paymentMethods->isEmpty())>
                @foreach ($paymentMethods as $method)
                    <option
                        value="{{ $method->id }}"
                        data-code="{{ $method->code }}"
                        data-type="{{ $method->type }}"
                        @selected($defaultPayment?->id === $method->id)
                    >{{ $method->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group cashier-cash-received" data-cash-received-group>
            <label class="form-label" for="cashier-amount-received">Uang diterima</label>
            <input class="form-control" id="cashier-amount-received" type="number" min="0" step="1" value="" inputmode="numeric" data-amount-received>
        </div>
        <div class="cashier-noncash-notice" data-noncash-notice hidden>
            Pastikan pembayaran non-tunai telah diterima.
        </div>
    </div>
    <div class="cashier-change">
        <span>Kembalian</span>
        <strong data-payment-change>Rp0</strong>
    </div>
    <p class="cashier-payment-error" role="alert" aria-live="assertive" data-payment-error></p>
    <div class="cashier-payment-actions">
        <button class="btn btn-primary" type="button" data-payment-action="print" disabled>Bayar &amp; Cetak</button>
        <button class="btn btn-outline" type="button" data-payment-action="no_print" disabled>Bayar Tanpa Cetak</button>
    </div>
</section>
