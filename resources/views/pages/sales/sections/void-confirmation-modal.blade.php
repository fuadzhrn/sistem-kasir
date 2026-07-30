@can('void', $sale)
    @php($requiresRefundConfirmation = $sale->paymentMethod?->type !== 'cash')
    <div
        class="modal"
        id="sale-void-modal"
        data-modal
        data-sale-void-modal
        @if ($errors->has('reason') || $errors->has('refund_confirmed') || $errors->has('confirmation')) data-sale-void-reopen @endif
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="sale-void-title"
    >
        <div class="modal__overlay" data-modal-overlay></div>
        <div class="modal__positioner">
            <div class="modal__dialog sale-void-modal__dialog" data-modal-dialog tabindex="-1">
                <div class="modal__header">
                    <div><span class="sale-void-modal__eyebrow">Tindakan Permanen</span><h2 id="sale-void-title">Batalkan Transaksi?</h2></div>
                    <button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button>
                </div>
                <form method="POST" action="{{ route('sales.void', $sale) }}" data-sale-void-form>
                    @csrf
                    @method('PATCH')
                    <div class="modal__body">
                        <dl class="sale-void-summary">
                            <div><dt>Nomor nota</dt><dd>{{ $sale->invoice_number }}</dd></div>
                            <div><dt>Total</dt><dd>{{ \App\Support\Format\Rupiah::format($sale->total) }}</dd></div>
                            <div><dt>Tanggal</dt><dd>{{ $sale->transaction_date->locale('id')->translatedFormat('d F Y, H.i') }}</dd></div>
                            <div><dt>Cabang</dt><dd>{{ $sale->branch?->name }}</dd></div>
                            <div><dt>Kasir</dt><dd>{{ $sale->cashier?->name }}</dd></div>
                            <div><dt>Metode</dt><dd>{{ $sale->payment_method_name }}</dd></div>
                            <div><dt>Jenis item</dt><dd>{{ $sale->items->count() }}</dd></div>
                        </dl>

                        <div class="alert alert-danger sale-void-warning">
                            Pembatalan diproses langsung oleh sistem, mengembalikan stok, mengoreksi nilai transaksi, dan tercatat pada riwayat aktivitas. Tindakan ini tidak dapat dibatalkan kembali.
                        </div>

                        @if ($requiresRefundConfirmation)
                            <div class="alert alert-warning">
                                Pembatalan di aplikasi tidak otomatis mengembalikan dana QRIS atau transfer.
                            </div>
                            <label class="sale-void-check">
                                <input
                                    id="void-refund-confirmed"
                                    type="checkbox"
                                    name="refund_confirmed"
                                    value="1"
                                    aria-describedby="void-refund-help @error('refund_confirmed') void-refund-error @enderror"
                                    @checked(old('refund_confirmed'))
                                    required
                                >
                                <span id="void-refund-help">Saya mengonfirmasi bahwa pengembalian dana telah ditangani atau akan ditangani secara manual.</span>
                            </label>
                            @error('refund_confirmed')<span class="form-error" id="void-refund-error">{{ $message }}</span>@enderror
                        @endif

                        <div class="form-group">
                            <label class="form-label" for="void-reason">Alasan Pembatalan *</label>
                            <textarea
                                class="form-control @error('reason') is-invalid @enderror"
                                id="void-reason"
                                name="reason"
                                rows="5"
                                minlength="10"
                                maxlength="1000"
                                aria-describedby="void-reason-help @error('reason') void-reason-error @enderror"
                                @error('reason') aria-invalid="true" @enderror
                                required
                            >{{ old('reason') }}</textarea>
                            <span class="form-help" id="void-reason-help">Jelaskan penyebab pembatalan, minimal 10 karakter.</span>
                            @error('reason')<span class="form-error" id="void-reason-error">{{ $message }}</span>@enderror
                        </div>

                        <label class="sale-void-check">
                            <input
                                id="void-confirmation"
                                type="checkbox"
                                name="confirmation"
                                value="1"
                                aria-describedby="void-confirmation-help @error('confirmation') void-confirmation-error @enderror"
                                @checked(old('confirmation'))
                                required
                            >
                            <span id="void-confirmation-help">Saya memahami transaksi akan dibatalkan secara permanen.</span>
                        </label>
                        @error('confirmation')<span class="form-error" id="void-confirmation-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal__actions">
                        <button class="btn btn-secondary" type="button" data-modal-close>Kembali</button>
                        <button class="btn btn-danger" type="submit" data-sale-void-submit>Batalkan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
