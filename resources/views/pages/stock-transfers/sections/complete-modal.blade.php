@can('complete', $stockTransfer)
    <div class="modal" id="transfer-complete-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="transfer-complete-title" hidden>
        <div class="modal__overlay" data-modal-overlay></div>
        <div class="modal__positioner">
            <div class="modal__dialog transfer-action-dialog" data-modal-dialog tabindex="-1">
                <div class="modal__header"><div><p class="eyebrow">Aksi final</p><h2 id="transfer-complete-title">Selesaikan Mutasi?</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button></div>
                <div class="modal__body"><p>Stok {{ $stockTransfer->sourceBranch->name }} akan dikurangi dan stok {{ $stockTransfer->destinationBranch->name }} ditambah dalam satu transaksi.</p><p class="transfer-action-warning">Tindakan ini tidak dapat dibatalkan.</p></div>
                <form action="{{ route('stock-transfers.complete', $stockTransfer) }}" method="POST" class="modal__actions" data-transfer-action-form>
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-secondary" type="button" data-modal-close>Periksa Lagi</button>
                    <button class="btn btn-primary" type="submit">Ya, Selesaikan</button>
                </form>
            </div>
        </div>
    </div>
@endcan
