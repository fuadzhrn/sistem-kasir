<div class="modal" id="expense-category-status-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-category-status-title">
    <div class="modal__overlay" data-modal-overlay></div><div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-category-status-title">Ubah Status Kategori</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">×</button></div>
        <form method="POST" data-expense-category-status-form>@csrf @method('PATCH')
            <input type="hidden" name="is_active" data-expense-category-status-value>
            <div class="modal__body"><p>Ubah status kategori <strong data-expense-category-status-name></strong>? Histori pengeluaran tidak akan dihapus.</p></div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-primary" type="submit" data-expense-category-submit>Ya, Ubah Status</button></div>
        </form>
    </div></div>
</div>
