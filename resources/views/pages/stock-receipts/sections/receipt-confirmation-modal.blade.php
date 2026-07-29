<div class="modal" id="receipt-confirmation-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="receipt-confirmation-title" hidden>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog receipt-confirmation-dialog" data-modal-dialog tabindex="-1">
            <div class="modal__header">
                <div><p class="eyebrow">Dokumen final</p><h2 id="receipt-confirmation-title">Simpan Barang Masuk?</h2></div>
                <button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button>
            </div>
            <div class="modal__body">
                <p class="receipt-confirmation-intro">
                    Pastikan cabang, supplier, produk, quantity, dan harga modal sudah benar.
                </p>
                <dl class="receipt-confirmation-list">
                    <div><dt>Cabang</dt><dd data-confirm-receipt-branch>-</dd></div>
                    <div><dt>Tanggal</dt><dd data-confirm-receipt-date>-</dd></div>
                    <div><dt>Supplier</dt><dd data-confirm-receipt-supplier>-</dd></div>
                    <div><dt>Jumlah jenis produk</dt><dd data-confirm-receipt-count>-</dd></div>
                    <div><dt>Total biaya preview</dt><dd data-confirm-receipt-total>-</dd></div>
                    <div><dt>Catatan</dt><dd data-confirm-receipt-notes>-</dd></div>
                </dl>
                <p class="receipt-confirmation-warning">Barang masuk akan menambah stok dan memperbarui harga modal rata-rata. Pastikan seluruh data sudah benar.</p>
                <p class="receipt-confirmation-final">Dokumen tidak dapat diedit atau dihapus setelah disimpan.</p>
            </div>
            <div class="modal__actions">
                <button class="btn btn-secondary" type="button" data-modal-close>Periksa Lagi</button>
                <button class="btn btn-primary" type="button" data-confirm-stock-receipt>Ya, Simpan Dokumen</button>
            </div>
        </div>
    </div>
</div>
