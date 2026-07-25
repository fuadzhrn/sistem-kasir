<div class="modal" id="branch-status-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="branch-status-title" hidden>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner">
        <div class="modal__dialog" data-modal-dialog tabindex="-1">
            <div class="modal__header">
                <div>
                    <p class="eyebrow">Status Cabang</p>
                    <h2 id="branch-status-title" data-status-title>Konfirmasi status</h2>
                </div>
                <button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">×</button>
            </div>
            <div class="modal__body">
                <p data-status-message>Pastikan perubahan status cabang sudah sesuai.</p>
            </div>
            <form data-status-form method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" data-status-value>
                <div class="modal__actions">
                    <button class="btn btn-secondary" type="button" data-modal-close>Batal</button>
                    <button class="btn btn-primary" type="submit" data-status-submit>Ya, lanjutkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
