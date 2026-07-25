<section class="card detail-card">
    <div class="section-heading"><div><span class="section-heading__eyebrow">Lampiran</span><h2>Bukti Pengeluaran</h2></div></div>
    @if ($expense->proof_file)
        <a class="expense-proof" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->proof_file) }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->proof_file) }}" alt="Bukti pengeluaran">
            <span>Buka gambar ukuran penuh</span>
        </a>
        @can('removeProof', $expense)
            <button class="btn btn-danger" type="button" data-expense-remove-proof data-action="{{ route('expenses.proof.destroy', $expense) }}">Hapus Bukti</button>
        @endcan
    @else
        <div class="empty-state"><h3>Tidak ada bukti</h3><p>Pengeluaran ini tidak memiliki lampiran gambar.</p></div>
    @endif
</section>
