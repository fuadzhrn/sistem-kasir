<div class="modal" id="expense-reject-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-reject-title">
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-reject-title">Tolak Pengeluaran?</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button></div>
        <form method="POST" data-expense-reject-form>
            @csrf @method('PATCH')
            <div class="modal__body">
                <dl class="expense-decision-summary">
                    <div><dt>Cabang</dt><dd data-expense-modal-branch></dd></div>
                    <div><dt>Tanggal</dt><dd data-expense-modal-date></dd></div>
                    <div><dt>Kategori</dt><dd data-expense-modal-category></dd></div>
                    <div><dt>Pencatat</dt><dd data-expense-modal-creator></dd></div>
                    <div><dt>Bukti</dt><dd data-expense-modal-proof></dd></div>
                    <div class="expense-decision-summary__description"><dt>Deskripsi</dt><dd data-expense-modal-description></dd></div>
                    <div class="expense-decision-summary__amount"><dt>Jumlah</dt><dd data-expense-modal-amount></dd></div>
                </dl>
                <p class="expense-decision-note">Pengeluaran yang ditolak tidak diperhitungkan sebagai pengurang laba bersih.</p>
                <div class="form-group">
                    <label class="form-label" for="rejection_reason">Alasan Penolakan *</label>
                    <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection_reason" name="rejection_reason" rows="5" minlength="10" maxlength="1000" aria-describedby="rejection-reason-help @error('rejection_reason') rejection-reason-error @enderror" @error('rejection_reason') aria-invalid="true" @enderror required>{{ old('rejection_reason') }}</textarea>
                    <span class="form-help" id="rejection-reason-help">Jelaskan alasan penolakan, minimal 10 karakter.</span>
                    @error('rejection_reason')<span class="form-error" id="rejection-reason-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-danger" type="submit" data-expense-action-submit>Tolak Pengeluaran</button></div>
        </form>
    </div></div>
</div>
