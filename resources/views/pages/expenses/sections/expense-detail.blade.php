<section class="card detail-card">
    <div class="section-heading">
        <div><span class="section-heading__eyebrow">Data Utama</span><h2>Rincian Pengeluaran</h2></div>
        @include('pages.expenses.sections.expense-status-badge')
    </div>
    <dl class="detail-list">
        <div><dt>Tanggal</dt><dd>{{ $expense->expense_date->translatedFormat('d F Y') }}</dd></div>
        <div><dt>Jumlah</dt><dd class="expense-amount">{{ \App\Support\Format\Rupiah::format($expense->amount) }}</dd></div>
        <div><dt>Cabang</dt><dd>{{ $expense->branch->name }}</dd></div>
        <div><dt>Kategori</dt><dd>{{ $expense->expenseCategory->name }}</dd></div>
        <div><dt>Dicatat oleh</dt><dd>{{ $expense->creator->name }}</dd></div>
        <div><dt>Waktu pencatatan</dt><dd>{{ $expense->created_at->translatedFormat('d F Y H:i') }}</dd></div>
        <div class="detail-list__full"><dt>Deskripsi</dt><dd class="expense-description">{{ $expense->description }}</dd></div>
        @if ($expense->isRejected())
            <div class="detail-list__full detail-list__danger"><dt>Alasan Penolakan</dt><dd>{{ $expense->rejection_reason }}</dd></div>
        @endif
    </dl>
    <div class="expense-profit-impact expense-profit-impact--{{ $expense->status }}" role="note">
        <strong>Dampak pada laba bersih</strong>
        @if ($expense->isApproved())
            <p>Pengeluaran ini diperhitungkan sebagai pengurang laba bersih.</p>
        @elseif ($expense->isRejected())
            <p>Pengeluaran ini tidak diperhitungkan sebagai pengurang laba bersih.</p>
        @else
            <p>Pengeluaran ini belum memengaruhi laba bersih. Hanya pengeluaran yang disetujui yang diperhitungkan.</p>
        @endif
    </div>
</section>
