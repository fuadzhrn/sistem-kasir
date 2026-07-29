<section class="table-card stock-history-table-card" aria-label="Riwayat perubahan stok">
    <div class="table-wrapper inventory-desktop-table">
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

    <div class="inventory-movement-list" aria-label="Riwayat perubahan stok versi mobile">
        @forelse ($movements as $movement)
            <article class="inventory-movement">
                <header class="inventory-movement__header">
                    <div>
                        <time class="inventory-movement__date" datetime="{{ $movement->created_at->toIso8601String() }}">
                            {{ $movement->created_at->format('d M Y, H:i') }}
                        </time>
                        <h2 class="inventory-movement__product">{{ $movement->product->name }}</h2>
                        <p class="inventory-movement__code">{{ $movement->product->code }}</p>
                    </div>
                    <span class="badge badge-info">
                        {{ $movementLabels[$movement->movement_type] ?? $movement->movement_type }}
                    </span>
                </header>

                @if ($showBranch)
                    <p class="inventory-movement__branch">
                        <span>Cabang</span>
                        <strong>{{ $movement->branch->code }} — {{ $movement->branch->name }}</strong>
                    </p>
                @endif

                <dl class="inventory-movement__values">
                    <div>
                        <dt>Quantity Perubahan</dt>
                        <dd class="inventory-movement__quantity {{ (float) $movement->quantity_change > 0 ? 'stock-change--positive' : 'stock-change--negative' }}">
                            {{ \App\Support\Format\Quantity::signed($movement->quantity_change) }}
                            {{ $movement->product->unit->symbol ?: $movement->product->unit->name }}
                        </dd>
                    </div>
                    <div>
                        <dt>Stok Sebelum</dt>
                        <dd>
                            {{ \App\Support\Format\Quantity::format($movement->quantity_before) }}
                            {{ $movement->product->unit->symbol ?: $movement->product->unit->name }}
                        </dd>
                    </div>
                    <div>
                        <dt>Stok Sesudah</dt>
                        <dd>
                            {{ \App\Support\Format\Quantity::format($movement->quantity_after) }}
                            {{ $movement->product->unit->symbol ?: $movement->product->unit->name }}
                        </dd>
                    </div>
                </dl>

                <div class="inventory-movement__meta">
                    <div>
                        <span>Dicatat oleh</span>
                        <strong>{{ $movement->creator?->name ?? 'Pengguna tidak tersedia' }}</strong>
                    </div>
                    <div>
                        <span>Alasan atau catatan</span>
                        <p>{{ $movement->notes ?: 'Tidak ada catatan.' }}</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="inventory-empty" role="status">
                <h2>Belum ada riwayat pergerakan stok</h2>
                <p>Riwayat akan tampil setelah terdapat perubahan stok yang sesuai dengan filter.</p>
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination riwayat stok">
            @if ($movements->onFirstPage())
                <span class="pagination-button pagination-button--text" aria-disabled="true">Sebelumnya</span>
            @else
                <a class="pagination-button pagination-button--text" href="{{ $movements->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $movements->currentPage() }}</span>
            @if ($movements->hasMorePages())
                <a class="pagination-button pagination-button--text" href="{{ $movements->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="pagination-button pagination-button--text" aria-disabled="true">Berikutnya</span>
            @endif
        </nav>
    </div>
</section>
