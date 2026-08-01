<div
    class="modal"
    id="modal-confirm"
    data-modal
    data-close-on-overlay="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-confirm-title"
    aria-describedby="modal-confirm-description"
    hidden
>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog" data-modal-dialog tabindex="-1">
            <div class="modal__header">
                <div>
                    <p class="eyebrow">Konfirmasi Tindakan</p>
                    <h2 id="modal-confirm-title">Konfirmasi Perubahan</h2>
                </div>
                <button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">×</button>
            </div>

            <div class="modal__body">
                <p id="modal-confirm-description" data-confirm-message>
                    Apakah Anda yakin ingin melanjutkan tindakan ini?
                </p>
            </div>

            <div class="modal__actions">
                <button class="btn btn-secondary" type="button" data-modal-close data-confirm-cancel>
                    Batal
                </button>
                <button class="btn btn-primary" type="button" data-confirm-accept>
                    Ya, lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>
