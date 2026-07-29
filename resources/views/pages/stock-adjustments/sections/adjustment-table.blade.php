<section class="table-card" aria-label="Daftar penyesuaian stok">
    <div class="table-wrapper stock-adjustments-desktop-table">
        <table class="table adjustment-table">
            <thead>
                <tr><th>No.</th><th>Nomor</th><th>Waktu</th><th>Cabang</th><th>Produk</th><th>Jenis</th><th>Stok sebelum</th><th>Perubahan</th><th>Stok sesudah</th><th>Alasan</th><th>Dibuat oleh</th><th class="table-actions">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustments->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $adjustment->adjustment_number }}</strong></td>
                        <td>{{ $adjustment->created_at->locale('id')->translatedFormat('d F Y, H.i') }}</td>
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

    <div class="stock-adjustments-list" aria-label="Riwayat penyesuaian stok versi mobile">
        @forelse ($adjustments as $adjustment)
            <article class="stock-adjustment-card">
                <header class="stock-adjustment-card__header">
                    <div>
                        <p class="stock-adjustment-card__number">{{ $adjustment->adjustment_number }}</p>
                        <time datetime="{{ $adjustment->created_at->toIso8601String() }}">
                            {{ $adjustment->created_at->locale('id')->translatedFormat('d F Y · H.i') }}
                        </time>
                    </div>
                    <span class="badge {{ $adjustment->quantity_change > 0 ? 'badge-success' : 'badge-warning' }}">
                        {{ $adjustment->type_label }}
                    </span>
                </header>

                <dl class="stock-adjustment-card__body">
                    <div class="stock-adjustment-card__row stock-adjustment-card__row--full">
                        <dt>Cabang</dt>
                        <dd>{{ $adjustment->branch->code }} — {{ $adjustment->branch->name }}</dd>
                    </div>
                    <div class="stock-adjustment-card__row stock-adjustment-card__row--full">
                        <dt>Produk</dt>
                        <dd>
                            <strong>{{ $adjustment->product->name }}</strong>
                            <span>{{ $adjustment->product->code }}</span>
                        </dd>
                    </div>
                    <div class="stock-adjustment-card__row stock-adjustment-card__row--quantity">
                        <dt>Quantity Perubahan</dt>
                        <dd class="{{ $adjustment->quantity_change > 0 ? 'adjustment-change--in' : 'adjustment-change--out' }}">
                            {{ \App\Support\Format\Quantity::signed($adjustment->quantity_change) }}
                        </dd>
                    </div>
                    <div class="stock-adjustment-card__row">
                        <dt>Stok Sebelum</dt>
                        <dd>{{ \App\Support\Format\Quantity::format($adjustment->quantity_before) }}</dd>
                    </div>
                    <div class="stock-adjustment-card__row">
                        <dt>Stok Sesudah</dt>
                        <dd><strong>{{ \App\Support\Format\Quantity::format($adjustment->quantity_after) }}</strong></dd>
                    </div>
                    <div class="stock-adjustment-card__row stock-adjustment-card__row--full">
                        <dt>Alasan</dt>
                        <dd class="stock-adjustment-card__reason">{{ $adjustment->reason }}</dd>
                    </div>
                    <div class="stock-adjustment-card__row stock-adjustment-card__row--full">
                        <dt>Dicatat oleh</dt>
                        <dd>{{ $adjustment->creator?->name ?? 'Pengguna tidak tersedia' }}</dd>
                    </div>
                </dl>

                <footer class="stock-adjustment-card__footer">
                    <a class="btn btn-primary" href="{{ route('stock-adjustments.show', $adjustment) }}">
                        Detail Penyesuaian
                    </a>
                </footer>
            </article>
        @empty
            <div class="stock-adjustments-empty" role="status">
                <h2>Belum ada riwayat penyesuaian stok</h2>
                <p>Data yang dicari belum tersedia atau tidak sesuai dengan filter aktif.</p>
                <a class="btn btn-primary" href="{{ route('stock-adjustments.create') }}">Tambah Penyesuaian Stok</a>
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $adjustments->firstItem() ?? 0 }}-{{ $adjustments->lastItem() ?? 0 }} dari {{ $adjustments->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination penyesuaian">
            @if ($adjustments->onFirstPage())<span class="pagination-button pagination-button--text" aria-disabled="true">Sebelumnya</span>@else<a class="pagination-button pagination-button--text" href="{{ $adjustments->previousPageUrl() }}">Sebelumnya</a>@endif
            <span class="pagination-button is-active" aria-current="page">{{ $adjustments->currentPage() }}</span>
            @if ($adjustments->hasMorePages())<a class="pagination-button pagination-button--text" href="{{ $adjustments->nextPageUrl() }}">Berikutnya</a>@else<span class="pagination-button pagination-button--text" aria-disabled="true">Berikutnya</span>@endif
        </nav>
    </div>
</section>
