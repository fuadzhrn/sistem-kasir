@can('cancel', $stockTransfer)
    <div class="modal" id="transfer-cancel-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="transfer-cancel-title" hidden>
        <div class="modal__overlay" data-modal-overlay></div>
        <div class="modal__positioner">
            <div class="modal__dialog transfer-action-dialog" data-modal-dialog tabindex="-1">
                <div class="modal__header"><div><p class="eyebrow">Permintaan pending</p><h2 id="transfer-cancel-title">Batalkan Permintaan?</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button></div>
                <form action="{{ route('stock-transfers.cancel', $stockTransfer) }}" method="POST" data-transfer-action-form>
                    @csrf
                    @method('PATCH')
                    <div class="modal__body form-group">
                        <label class="form-label" for="transfer-cancellation-reason">Alasan pembatalan (opsional)</label>
                        <textarea class="form-textarea" id="transfer-cancellation-reason" name="cancellation_reason" minlength="5" maxlength="1000"></textarea>
                    </div>
                    <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Kembali</button><button class="btn btn-outline" type="submit">Batalkan Permintaan</button></div>
                </form>
            </div>
        </div>
    </div>
@endcan
