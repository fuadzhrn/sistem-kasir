<div class="modal" id="adjustment-confirmation-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="adjustment-confirmation-title" hidden>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog adjustment-confirmation-dialog" data-modal-dialog tabindex="-1">
            <div class="modal__header">
                <div><p class="eyebrow">Catatan permanen</p><h2 id="adjustment-confirmation-title">Simpan Penyesuaian Stok?</h2></div>
                <button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button>
            </div>
            <div class="modal__body">
                <p class="adjustment-confirmation-intro">
                    Perubahan stok akan dicatat pada riwayat pergerakan dan tidak dapat diedit atau dihapus.
                </p>
                <dl class="adjustment-confirmation-list">
                    <div><dt>Cabang</dt><dd data-confirm-adjustment-branch>-</dd></div>
                    <div><dt>Produk</dt><dd data-confirm-adjustment-product>-</dd></div>
                    <div><dt>Jenis</dt><dd data-confirm-adjustment-type>-</dd></div>
                    <div><dt>Stok sebelum</dt><dd data-confirm-adjustment-before>-</dd></div>
                    <div><dt>Perubahan</dt><dd data-confirm-adjustment-change>-</dd></div>
                    <div><dt>Stok sesudah</dt><dd data-confirm-adjustment-after>-</dd></div>
                    <div><dt>Alasan</dt><dd data-confirm-adjustment-reason>-</dd></div>
                </dl>
                <p class="adjustment-confirmation-warning">Penyesuaian stok akan dicatat secara permanen dan tidak dapat diedit atau dihapus.</p>
            </div>
            <div class="modal__actions">
                <button class="btn btn-secondary" type="button" data-modal-close>Periksa Lagi</button>
                <button class="btn btn-primary" type="button" data-confirm-adjustment>Ya, Simpan Penyesuaian</button>
            </div>
        </div>
    </div>
</div>
