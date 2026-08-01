<div class="modal" id="cashier-payment-preview-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="cashier-preview-title" hidden>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog cashier-preview-modal" data-modal-dialog tabindex="-1">
            <div class="modal__header"><div><p class="eyebrow">Transaksi tersimpan</p><h2 id="cashier-preview-title">Pembayaran Berhasil</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">×</button></div>
            <div class="modal__body">
                <dl class="cashier-preview-list">
                    <div><dt>Nomor Nota</dt><dd data-preview-invoice>-</dd></div>
                    <div><dt>Cabang</dt><dd data-preview-branch>-</dd></div>
                    <div><dt>Jumlah item</dt><dd data-preview-items>0</dd></div>
                    <div><dt>Subtotal</dt><dd data-preview-subtotal>Rp0</dd></div>
                    <div><dt>Diskon</dt><dd data-preview-discount>Rp0</dd></div>
                    <div><dt>Total</dt><dd data-preview-total>Rp0</dd></div>
                    <div><dt>Metode</dt><dd data-preview-method>-</dd></div>
                    <div data-preview-cash-row><dt>Uang diterima</dt><dd data-preview-received>Rp0</dd></div>
                    <div><dt>Kembalian</dt><dd data-preview-change>Rp0</dd></div>
                </dl>
                <div class="alert alert-success" data-preview-message>Transaksi berhasil disimpan.</div>
            </div>
            <div class="modal__actions">
                <a
                    class="btn btn-outline"
                    href="#"
                    target="receipt-print"
                    data-preview-print-link
                    hidden
                >Buka Struk untuk Dicetak</a>
                <button class="btn btn-primary" type="button" data-modal-close>Transaksi Berikutnya</button>
            </div>
        </div>
    </div>
</div>
