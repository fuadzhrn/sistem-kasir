<section class="table-card" aria-label="Daftar penyesuaian stok">
    <div class="table-wrapper">
        <table class="table adjustment-table">
            <thead>
                <tr><th>No.</th><th>Nomor</th><th>Waktu</th><th>Cabang</th><th>Produk</th><th>Jenis</th><th>Stok sebelum</th><th>Perubahan</th><th>Stok sesudah</th><th>Alasan</th><th>Dibuat oleh</th><th class="table-actions">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustments->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $adjustment->adjustment_number }}</strong></td>
                        <td>{{ $adjustment->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $adjustment->branch->code }} - {{ $adjustment->branch->name }}</td>
                        <td><strong>{{ $adjustment->product->code }}</strong><small class="table-secondary">{{ $adjustment->product->name }}</small></td>
                        <td><span class="badge {{ $adjustment->quantity_change > 0 ? 'badge-success' : 'badge-warning' }}">{{ $adjustment->type_label }}</span></td>
                        <td>{{ \App\Support\Format\Quantity::format($adjustment->quantity_before) }}</td>
                        <td class="{{ $adjustment->quantity_change > 0 ? 'adjustment-change--in' : 'adjustment-change--out' }}">{{ \App\Support\Format\Quantity::signed($adjustment->quantity_change) }}</td>
                        <td><strong>{{ \App\Support\Format\Quantity::format($adjustment->quantity_after) }}</strong></td>
                        <td class="adjustment-reason-cell">{{ \Illuminate\Support\Str::limit($adjustment->reason, 72) }}</td>
                        <td>{{ $adjustment->creator?->name ?? 'Pengguna tidak tersedia' }}</td>
                        <td class="table-actions"><a class="btn btn-sm btn-outline" href="{{ route('stock-adjustments.show', $adjustment) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr class="table-empty-row"><td colspan="12">Belum ada penyesuaian stok yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $adjustments->firstItem() ?? 0 }}-{{ $adjustments->lastItem() ?? 0 }} dari {{ $adjustments->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination penyesuaian">
            @if ($adjustments->onFirstPage())<span class="pagination-button" aria-disabled="true">&lsaquo;</span>@else<a class="pagination-button" href="{{ $adjustments->previousPageUrl() }}">&lsaquo;</a>@endif
            <span class="pagination-button is-active">{{ $adjustments->currentPage() }}</span>
            @if ($adjustments->hasMorePages())<a class="pagination-button" href="{{ $adjustments->nextPageUrl() }}">&rsaquo;</a>@else<span class="pagination-button" aria-disabled="true">&rsaquo;</span>@endif
        </nav>
    </div>
</section>
