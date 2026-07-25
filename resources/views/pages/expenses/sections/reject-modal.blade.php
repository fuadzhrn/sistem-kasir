<div class="modal" id="expense-reject-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-reject-title">
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-reject-title">Tolak Pengeluaran</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button></div>
        <form method="POST" data-expense-reject-form>
            @csrf @method('PATCH')
            <div class="modal__body">
                <p>Tolak pengeluaran <strong data-expense-modal-description></strong> senilai <strong data-expense-modal-amount></strong>? Keputusan ini tidak dapat dibatalkan.</p>
                <div class="form-group">
                    <label class="form-label" for="rejection_reason">Alasan Penolakan *</label>
                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" minlength="5" maxlength="1000" required></textarea>
                </div>
            </div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit">Ya, Tolak</button></div>
        </form>
    </div></div>
</div>
