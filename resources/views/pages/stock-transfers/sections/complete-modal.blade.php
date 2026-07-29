@can('complete', $stockTransfer)
    <div class="modal" id="transfer-complete-modal" data-modal data-close-on-overlay="true" role="dialog" aria-modal="true" aria-labelledby="transfer-complete-title" hidden>
        <div class="modal__overlay" data-modal-overlay></div>
        <div class="modal__positioner">
            <div class="modal__dialog transfer-action-dialog" data-modal-dialog tabindex="-1">
                <div class="modal__header"><div><p class="eyebrow">Aksi final</p><h2 id="transfer-complete-title">Selesaikan Mutasi?</h2></div><button class="modal__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button></div>
                <div class="modal__body">
                    <p>Pastikan barang akan dipindahkan dari cabang asal ke cabang tujuan yang benar.</p>
                    <dl class="transfer-confirmation-list">
                        <div><dt>Cabang asal</dt><dd>{{ $stockTransfer->sourceBranch->name }}</dd></div>
                        <div><dt>Cabang tujuan</dt><dd>{{ $stockTransfer->destinationBranch->name }}</dd></div>
                        <div><dt>Produk</dt><dd>{{ $stockTransfer->product->code }} - {{ $stockTransfer->product->name }}</dd></div>
                        <div>
                            <dt>Quantity</dt>
                            <dd>{{ \App\Support\Format\Quantity::format($stockTransfer->quantity) }} {{ $stockTransfer->product->unit->symbol ?: $stockTransfer->product->unit->name }}</dd>
                        </div>
                        <div class="transfer-confirmation-list__full"><dt>Catatan</dt><dd>{{ $stockTransfer->notes }}</dd></div>
                    </dl>
                    <p class="transfer-action-warning">Stok kedua cabang akan diperbarui dalam satu transaksi. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
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
