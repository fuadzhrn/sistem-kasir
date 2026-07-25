@can('reject', $stockTransfer)
    <div class="modal" id="transfer-reject-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="transfer-reject-title" hidden>
        <div class="modal__overlay" data-modal-overlay></div>
        <div class="modal__positioner">
            <div class="modal__dialog transfer-action-dialog" data-modal-dialog tabindex="-1">
                <div class="modal__header"><div><p class="eyebrow">Tanpa perubahan stok</p><h2 id="transfer-reject-title">Tolak Permintaan?</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button></div>
                <form action="{{ route('stock-transfers.reject', $stockTransfer) }}" method="POST" data-transfer-action-form>
                    @csrf
                    @method('PATCH')
                    <div class="modal__body form-group">
                        <label class="form-label" for="transfer-rejection-reason">Alasan penolakan <span class="form-required">*</span></label>
                        <textarea class="form-textarea" id="transfer-rejection-reason" name="rejection_reason" minlength="10" maxlength="1000" required></textarea>
                    </div>
                    <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit">Tolak Permintaan</button></div>
                </form>
            </div>
        </div>
    </div>
@endcan
