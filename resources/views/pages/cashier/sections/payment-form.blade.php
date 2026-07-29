@php
    $defaultPayment = $paymentMethods->firstWhere('code', 'CASH') ?? $paymentMethods->first();
@endphp
<div class="cashier-payment-sheet" id="cashier-payment-sheet" data-payment-sheet aria-hidden="false">
    <button class="cashier-payment-sheet__overlay" type="button" aria-label="Tutup pembayaran" data-payment-sheet-close></button>
    <section class="cashier-payment" aria-labelledby="cashier-payment-heading" data-payment-dialog tabindex="-1">
        <div class="cashier-payment__heading">
            <div>
                <p class="eyebrow">Selesaikan transaksi</p>
                <h3 id="cashier-payment-heading">Pembayaran</h3>
            </div>
            <button class="cashier-payment__close" type="button" aria-label="Tutup pembayaran" data-payment-sheet-close>
                ×
            </button>
        </div>

        <dl class="cashier-payment-sheet__summary" aria-label="Ringkasan pembayaran">
            <div><dt>Jenis produk</dt><dd data-payment-sheet-items>0</dd></div>
            <div><dt>Subtotal</dt><dd data-payment-sheet-subtotal>Rp0</dd></div>
            <div><dt>Diskon</dt><dd data-payment-sheet-discount>Rp0</dd></div>
            <div class="cashier-payment-sheet__total"><dt>Total dibayar</dt><dd data-payment-sheet-total>Rp0</dd></div>
        </dl>

        @if ($paymentMethods->isEmpty())
            <div class="alert alert-warning" role="alert">Belum ada metode pembayaran aktif.</div>
        @endif

        <div class="cashier-payment__grid">
            <div class="form-group">
                <label class="form-label" for="cashier-discount">Diskon</label>
                <input
                    class="form-control"
                    id="cashier-discount"
                    type="text"
                    value="0"
                    inputmode="numeric"
                    autocomplete="off"
                    aria-describedby="cashier-discount-help cashier-payment-error"
                    data-payment-discount
                    data-rupiah-input
                >
                <span class="form-help" id="cashier-discount-help">
                    @if ($discountRestricted)
                        Batas akun: {{ \App\Support\Format\Rupiah::format($maximumDiscount) }}
                    @else
                        Maksimal sebesar subtotal transaksi.
                    @endif
                </span>
            </div>
            <div class="form-group">
                <label class="form-label" for="cashier-payment-method">Metode pembayaran</label>
                <select
                    class="form-select"
                    id="cashier-payment-method"
                    aria-describedby="cashier-payment-error"
                    data-payment-method
                    @disabled($paymentMethods->isEmpty())
                >
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
                <input
                    class="form-control"
                    id="cashier-amount-received"
                    type="text"
                    value=""
                    inputmode="numeric"
                    autocomplete="off"
                    aria-describedby="cashier-payment-error"
                    data-amount-received
                    data-rupiah-input
                >
            </div>
            <div class="cashier-noncash-notice" data-noncash-notice hidden>
                Pastikan pembayaran non-tunai telah diterima.
            </div>
        </div>

        <div class="cashier-change">
            <span>Kembalian</span>
            <strong data-payment-change>Rp0</strong>
        </div>
        <p class="cashier-payment-error" id="cashier-payment-error" role="alert" aria-live="assertive" data-payment-error></p>
        <div class="cashier-payment-actions">
            <button class="btn btn-primary" type="button" data-payment-action="print" disabled>Proses Pembayaran &amp; Cetak</button>
            <button class="btn btn-outline" type="button" data-payment-action="no_print" disabled>Proses Tanpa Cetak</button>
        </div>
    </section>
</div>
