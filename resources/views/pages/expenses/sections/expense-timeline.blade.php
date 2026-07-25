<section class="card detail-card expense-timeline-card">
    <div class="section-heading"><div><span class="section-heading__eyebrow">Jejak Proses</span><h2>Linimasa Pengeluaran</h2></div></div>
    <ol class="expense-timeline">
        <li>
            <strong>Pengeluaran dicatat</strong>
            <span>{{ $expense->creator->name }} · {{ $expense->created_at->translatedFormat('d M Y H:i') }}</span>
        </li>
        @if ($expense->updated_at->ne($expense->created_at))
            <li>
                <strong>Data terakhir diperbarui</strong>
                <span>{{ $expense->updater?->name ?? 'Sistem' }} · {{ $expense->updated_at->translatedFormat('d M Y H:i') }}</span>
            </li>
        @endif
        @if ($expense->isApproved())
            <li class="is-success">
                <strong>Pengeluaran disetujui</strong>
                <span>{{ $expense->approver?->name ?? 'Owner' }} · {{ $expense->approved_at?->translatedFormat('d M Y H:i') }}</span>
            </li>
        @elseif ($expense->isRejected())
            <li class="is-danger">
                <strong>Pengeluaran ditolak</strong>
                <span>{{ $expense->rejector?->name ?? 'Owner' }} · {{ $expense->rejected_at?->translatedFormat('d M Y H:i') }}</span>
            </li>
        @else
            <li class="is-pending"><strong>Menunggu keputusan owner</strong><span>Belum diproses</span></li>
        @endif
    </ol>
</section>
