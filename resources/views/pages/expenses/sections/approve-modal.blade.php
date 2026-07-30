<div class="modal" id="expense-approve-modal" data-modal hidden role="dialog" aria-modal="true" aria-labelledby="expense-approve-title">
    <div class="modal__overlay" data-modal-overlay></div>
    <div class="modal__positioner"><div class="modal__dialog" data-modal-dialog tabindex="-1">
        <div class="modal__header"><h2 id="expense-approve-title">Setujui Pengeluaran?</h2><button class="modal__close" type="button" data-modal-close aria-label="Tutup">&times;</button></div>
        <form method="POST" data-expense-approve-form>
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
                <p class="expense-decision-note">Setelah disetujui, pengeluaran akan diperhitungkan sebagai pengurang laba bersih sesuai mekanisme sistem.</p>
            </div>
            <div class="modal__actions"><button class="btn btn-secondary" type="button" data-modal-close>Batal</button><button class="btn btn-success" type="submit" data-expense-action-submit>Setujui Pengeluaran</button></div>
        </form>
    </div></div>
</div>
