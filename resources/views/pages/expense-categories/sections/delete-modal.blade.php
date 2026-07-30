<div class="modal" id="expense-category-delete-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-category-delete-title">
    <div class="modal__overlay" data-modal-overlay></div><div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-category-delete-title">Hapus Kategori</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">×</button></div>
        <form method="POST" data-expense-category-delete-form>@csrf @method('DELETE')
            <div class="modal__body"><p>Hapus kategori <strong data-expense-category-delete-name></strong>? Hanya kategori yang belum digunakan yang dapat dihapus.</p></div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit" data-expense-category-submit>Hapus Kategori</button></div>
        </form>
    </div></div>
</div>
