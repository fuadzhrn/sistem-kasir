<div class="modal" id="category-delete-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="category-delete-title" hidden>
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><div><p class="eyebrow">Hapus Kategori</p><h2 id="category-delete-title">Konfirmasi penghapusan</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup">×</button></div>
        <div class="modal__body"><p><strong data-delete-name></strong> akan dihapus permanen. Data yang sudah digunakan produk tidak dapat dihapus.</p></div>
        <form data-delete-form method="POST">@csrf @method('DELETE')<div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit">Hapus Kategori</button></div></form>
    </div></div>
</div>
