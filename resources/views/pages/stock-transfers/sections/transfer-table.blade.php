<section class="table-card" aria-label="Daftar mutasi stok">
    <div class="table-wrapper stock-transfers-desktop-table">
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
                        <td>{{ $transfer->created_at->locale('id')->translatedFormat('d F Y, H.i') }}</td>
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

    <div class="stock-transfer-list" aria-label="Riwayat mutasi stok versi mobile">
        @forelse ($transfers as $transfer)
            @php
                $statusClass = match ($transfer->status) {
                    \App\Models\StockTransfer::STATUS_COMPLETED => 'badge-success',
                    \App\Models\StockTransfer::STATUS_REJECTED => 'badge-danger',
                    \App\Models\StockTransfer::STATUS_CANCELLED => 'badge-neutral',
                    default => 'badge-warning',
                };
            @endphp
            <article class="stock-transfer-card">
                <header class="stock-transfer-card__header">
                    <div>
                        <p class="stock-transfer-card__number">{{ $transfer->transfer_number }}</p>
                        <time datetime="{{ $transfer->created_at->toIso8601String() }}">
                            {{ $transfer->created_at->locale('id')->translatedFormat('d F Y · H.i') }}
                        </time>
                    </div>
                    <span class="badge {{ $statusClass }}">{{ $transfer->status_label }}</span>
                </header>

                <dl class="stock-transfer-card__body">
                    <div class="stock-transfer-card__branch stock-transfer-card__branch--source">
                        <dt>Cabang Asal</dt>
                        <dd>{{ $transfer->sourceBranch->code }} — {{ $transfer->sourceBranch->name }}</dd>
                    </div>
                    <div class="stock-transfer-card__branch stock-transfer-card__branch--destination">
                        <dt>Cabang Tujuan</dt>
                        <dd>{{ $transfer->destinationBranch->code }} — {{ $transfer->destinationBranch->name }}</dd>
                    </div>
                    <div class="stock-transfer-card__row stock-transfer-card__row--full">
                        <dt>Produk</dt>
                        <dd>
                            <strong>{{ $transfer->product->name }}</strong>
                            <span>{{ $transfer->product->code }}</span>
                        </dd>
                    </div>
                    <div class="stock-transfer-card__row stock-transfer-card__row--quantity">
                        <dt>Quantity Mutasi</dt>
                        <dd>{{ \App\Support\Format\Quantity::format($transfer->quantity) }}</dd>
                    </div>
                    <div class="stock-transfer-card__row">
                        <dt>Diminta oleh</dt>
                        <dd>{{ $transfer->requester?->name ?? 'Pengguna tidak tersedia' }}</dd>
                    </div>
                    <div class="stock-transfer-card__row">
                        <dt>Diperiksa oleh</dt>
                        <dd>{{ $transfer->reviewer?->name ?? 'Belum diperiksa' }}</dd>
                    </div>
                </dl>

                <footer class="stock-transfer-card__footer">
                    <a class="btn btn-primary" href="{{ route('stock-transfers.show', $transfer) }}">
                        Detail Mutasi
                    </a>
                </footer>
            </article>
        @empty
            <div class="stock-transfers-empty" role="status">
                <h2>Belum ada riwayat mutasi stok</h2>
                <p>Data mutasi belum tersedia atau tidak sesuai dengan filter aktif.</p>
                <a class="btn btn-primary" href="{{ route('stock-transfers.create') }}">Buat Permintaan Mutasi</a>
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $transfers->firstItem() ?? 0 }}-{{ $transfers->lastItem() ?? 0 }} dari {{ $transfers->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination mutasi">
            @if ($transfers->onFirstPage())<span class="pagination-button pagination-button--text" aria-disabled="true">Sebelumnya</span>@else<a class="pagination-button pagination-button--text" href="{{ $transfers->previousPageUrl() }}">Sebelumnya</a>@endif
            <span class="pagination-button is-active" aria-current="page">{{ $transfers->currentPage() }}</span>
            @if ($transfers->hasMorePages())<a class="pagination-button pagination-button--text" href="{{ $transfers->nextPageUrl() }}">Berikutnya</a>@else<span class="pagination-button pagination-button--text" aria-disabled="true">Berikutnya</span>@endif
        </nav>
    </div>
</section>
