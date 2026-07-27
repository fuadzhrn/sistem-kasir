<section class="card receipt-preview-card" aria-labelledby="receipt-preview-title">
    <div class="card__header"><div><p class="eyebrow">Preview Lokal</p><h2 id="receipt-preview-title">Contoh Struk</h2></div><span data-preview-width>{{ $settings['receipt.default_paper_width'] }} mm</span></div>
    <div class="card__body">
        <article class="settings-receipt" data-settings-receipt>
            <div class="settings-receipt__logo" data-preview-logo @if (! $logoUrl || ! $settings['receipt.show_logo']) hidden @endif>
                @if ($logoUrl)<img src="{{ $logoUrl }}" alt="Logo preview toko">@endif
            </div>
            <h3 data-preview-output="store-name">{{ $settings['store.name'] }}</h3>
            <p>Cabang Utama</p>
            <p data-preview-output="store-address">{{ $settings['store.address'] }}</p>
            <p data-preview-output="store-phone">@if ($settings['store.phone'])Telp. {{ $settings['store.phone'] }}@endif</p>
            <hr>
            <dl><div><dt>Nomor</dt><dd data-preview-invoice>UTM-{{ now()->format('Ymd') }}-0001</dd></div><div><dt>Kasir</dt><dd>Kasir Contoh</dd></div></dl>
            <hr>
            <div class="settings-receipt__item"><span>Pupuk Organik × 1</span><strong>Rp75.000</strong></div>
            <div class="settings-receipt__item"><span>Benih Jagung × 2</span><strong>Rp50.000</strong></div>
            <hr>
            <dl><div><dt>Subtotal</dt><dd>Rp125.000</dd></div><div><dt>Diskon</dt><dd>Rp5.000</dd></div><div class="is-total"><dt>Total</dt><dd>Rp120.000</dd></div><div><dt>Metode</dt><dd>Tunai</dd></div><div><dt>Bayar</dt><dd>Rp150.000</dd></div><div><dt>Kembali</dt><dd>Rp30.000</dd></div></dl>
            <hr>
            <p class="settings-receipt__additional" data-preview-output="additional">{{ $settings['receipt.additional_information'] }}</p>
            <p class="settings-receipt__footer" data-preview-output="footer">{{ $settings['receipt.footer_message'] ?: 'Terima kasih telah berbelanja.' }}</p>
        </article>
        <p class="settings-note">Preview memakai data contoh dan tidak membuat transaksi atau nomor nota nyata.</p>
    </div>
</section>
