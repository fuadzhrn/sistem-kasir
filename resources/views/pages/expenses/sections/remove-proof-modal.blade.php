<div class="modal" id="expense-remove-proof-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-remove-proof-title">
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-remove-proof-title">Hapus Bukti Pengeluaran</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button></div>
        <form method="POST" data-expense-remove-proof-form>
            @csrf @method('DELETE')
            <div class="modal__body"><p>Hapus gambar bukti dari pengeluaran ini? File yang telah dihapus tidak dapat dipulihkan.</p></div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit" data-expense-action-submit>Ya, Hapus Bukti</button></div>
        </form>
    </div></div>
</div>
