<section class="table-card" aria-label="Daftar mutasi stok">
    <div class="table-wrapper">
        <table class="table transfer-table">
            <thead>
                <tr><th>No.</th><th>Nomor</th><th>Waktu</th><th>Cabang asal</th><th>Cabang tujuan</th><th>Produk</th><th>Quantity</th><th>Status</th><th>Diminta oleh</th><th>Diperiksa oleh</th><th class="table-actions">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($transfers as $transfer)
                    @php
                        $statusClass = match ($transfer->status) {
                            \App\Models\StockTransfer::STATUS_COMPLETED => 'badge-success',
                            \App\Models\StockTransfer::STATUS_REJECTED => 'badge-danger',
                            \App\Models\StockTransfer::STATUS_CANCELLED => 'badge-neutral',
                            default => 'badge-warning',
                        };
                    @endphp
                    <tr>
                        <td>{{ $transfers->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $transfer->transfer_number }}</strong></td>
                        <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $transfer->sourceBranch->code }} - {{ $transfer->sourceBranch->name }}</td>
                        <td>{{ $transfer->destinationBranch->code }} - {{ $transfer->destinationBranch->name }}</td>
                        <td><strong>{{ $transfer->product->code }}</strong><small class="table-secondary">{{ $transfer->product->name }}</small></td>
                        <td>{{ \App\Support\Format\Quantity::format($transfer->quantity) }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ $transfer->status_label }}</span></td>
                        <td>{{ $transfer->requester?->name ?? 'Pengguna tidak tersedia' }}</td>
                        <td>{{ $transfer->reviewer?->name ?? '-' }}</td>
                        <td class="table-actions"><a class="btn btn-sm btn-outline" href="{{ route('stock-transfers.show', $transfer) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr class="table-empty-row"><td colspan="11">Belum ada mutasi stok yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} dari {{ $transfers->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination mutasi">
            @if ($transfers->onFirstPage())<span class="pagination-button" aria-disabled="true">&lsaquo;</span>@else<a class="pagination-button" href="{{ $transfers->previousPageUrl() }}">&lsaquo;</a>@endif
            <span class="pagination-button is-active">{{ $transfers->currentPage() }}</span>
            @if ($transfers->hasMorePages())<a class="pagination-button" href="{{ $transfers->nextPageUrl() }}">&rsaquo;</a>@else<span class="pagination-button" aria-disabled="true">&rsaquo;</span>@endif
        </nav>
    </div>
</section>
