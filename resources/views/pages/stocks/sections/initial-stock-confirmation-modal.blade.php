<div
    class="modal"
    id="initial-stock-confirmation-modal"
    data-modal
    data-close-on-overlay="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="initial-stock-confirmation-title"
    hidden
>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog" data-modal-dialog tabindex="-1">
            <div class="modal__header">
                <div>
                    <p class="eyebrow">Catatan permanen</p>
                    <h2 id="initial-stock-confirmation-title">Konfirmasi Stok Awal</h2>
                </div>
                <button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">×</button>
            </div>
            <div class="modal__body">
                <dl class="stock-confirmation-list">
                    <div><dt>Cabang</dt><dd data-confirm-branch>—</dd></div>
                    <div><dt>Produk</dt><dd data-confirm-product>—</dd></div>
                    <div><dt>Stok sebelumnya</dt><dd data-confirm-before>—</dd></div>
                    <div><dt>Stok setelah perubahan</dt><dd data-confirm-after>—</dd></div>
                    <div><dt>Perubahan</dt><dd data-confirm-change>—</dd></div>
                    <div><dt>Alasan</dt><dd data-confirm-reason>—</dd></div>
                </dl>
                <p class="stock-confirmation-warning">Perubahan ini akan dicatat permanen dalam riwayat stok.</p>
            </div>
            <div class="modal__actions">
                <button class="btn btn-secondary" type="button" data-modal-close>Batal</button>
                <button class="btn btn-primary" type="button" data-confirm-initial-stock>Ya, Simpan</button>
            </div>
        </div>
    </div>
</div>
