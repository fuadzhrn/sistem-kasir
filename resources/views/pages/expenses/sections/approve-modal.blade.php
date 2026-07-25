<div class="modal" id="expense-approve-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-approve-title">
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-approve-title">Setujui Pengeluaran</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button></div>
        <form method="POST" data-expense-approve-form>
            @csrf @method('PATCH')
            <div class="modal__body"><p>Setujui pengeluaran <strong data-expense-modal-description></strong> senilai <strong data-expense-modal-amount></strong>? Keputusan ini tidak dapat dibatalkan.</p></div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-success" type="submit">Ya, Setujui</button></div>
        </form>
    </div></div>
</div>
