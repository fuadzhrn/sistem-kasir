<section class="table-card" aria-label="Daftar barang masuk">
    <div class="table-wrapper">
        <table class="table receipt-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nomor penerimaan</th>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Supplier</th>
                    <th>Jumlah produk</th>
                    <th>Total biaya</th>
                    <th>Dibuat oleh</th>
                    <th>Waktu input</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receipts as $receipt)
                    <tr>
                        <td>{{ $receipts->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $receipt->receipt_number }}</strong></td>
                        <td>{{ $receipt->receipt_date->format('d/m/Y') }}</td>
                        <td>{{ $receipt->branch->code }} - {{ $receipt->branch->name }}</td>
                        <td>{{ $receipt->supplier_name ?: 'Tidak dicantumkan' }}</td>
                        <td>{{ $receipt->items_count }} produk</td>
                        <td><strong>{{ \App\Support\Format\Rupiah::format($receipt->total_cost) }}</strong></td>
                        <td>{{ $receipt->creator?->name ?? 'Pengguna tidak tersedia' }}</td>
                        <td>{{ $receipt->created_at->format('d/m/Y H:i') }}</td>
                        <td class="table-actions">
                            <a class="btn btn-sm btn-outline" href="{{ route('stock-receipts.show', $receipt) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="10">Belum ada penerimaan barang yang sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $receipts->firstItem() ?? 0 }}-{{ $receipts->lastItem() ?? 0 }} dari {{ $receipts->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination barang masuk">
            @if ($receipts->onFirstPage())
                <span class="pagination-button" aria-disabled="true">&lsaquo;</span>
            @else
                <a class="pagination-button" href="{{ $receipts->previousPageUrl() }}">&lsaquo;</a>
            @endif
            <span class="pagination-button is-active">{{ $receipts->currentPage() }}</span>
            @if ($receipts->hasMorePages())
                <a class="pagination-button" href="{{ $receipts->nextPageUrl() }}">&rsaquo;</a>
            @else
                <span class="pagination-button" aria-disabled="true">&rsaquo;</span>
            @endif
        </nav>
    </div>
</section>
