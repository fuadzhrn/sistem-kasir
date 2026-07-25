<section class="table-card stock-history-table-card" aria-label="Riwayat perubahan stok">
    <div class="table-wrapper">
        <table class="table stock-history-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    @if ($showBranch)<th>Cabang</th>@endif
                    <th>Produk</th>
                    <th>Jenis</th>
                    <th class="table-number">Sebelum</th>
                    <th class="table-number">Perubahan</th>
                    <th class="table-number">Setelah</th>
                    <th>Satuan</th>
                    <th>Alasan</th>
                    <th>Dilakukan Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d M Y, H:i') }}</td>
                        @if ($showBranch)
                            <td>{{ $movement->branch->code }}<small class="table-secondary">{{ $movement->branch->name }}</small></td>
                        @endif
                        <td><strong>{{ $movement->product->code }}</strong><small class="table-secondary">{{ $movement->product->name }}</small></td>
                        <td><span class="badge badge-info">{{ $movementLabels[$movement->movement_type] ?? $movement->movement_type }}</span></td>
                        <td class="table-number">{{ \App\Support\Format\Quantity::format($movement->quantity_before) }}</td>
                        <td class="table-number {{ (float) $movement->quantity_change > 0 ? 'stock-change--positive' : 'stock-change--negative' }}">
                            {{ \App\Support\Format\Quantity::signed($movement->quantity_change) }}
                        </td>
                        <td class="table-number">{{ \App\Support\Format\Quantity::format($movement->quantity_after) }}</td>
                        <td>{{ $movement->product->unit->symbol ?: $movement->product->unit->name }}</td>
                        <td class="stock-history-notes">{{ $movement->notes }}</td>
                        <td>{{ $movement->creator?->name ?? 'Pengguna tidak tersedia' }}</td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="{{ $showBranch ? 10 : 9 }}">Belum ada riwayat stok yang sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination riwayat stok">
            @if ($movements->onFirstPage())
                <span class="pagination-button" aria-disabled="true">‹</span>
            @else
                <a class="pagination-button" href="{{ $movements->previousPageUrl() }}">‹</a>
            @endif
            <span class="pagination-button is-active">{{ $movements->currentPage() }}</span>
            @if ($movements->hasMorePages())
                <a class="pagination-button" href="{{ $movements->nextPageUrl() }}">›</a>
            @else
                <span class="pagination-button" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
</section>
