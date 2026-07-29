<section class="table-card" aria-label="Daftar barang masuk">
    <div class="table-wrapper goods-receipts-desktop-table">
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
                        <td>{{ $receipt->receipt_date->locale('id')->translatedFormat('d F Y') }}</td>
                        <td>{{ $receipt->branch->code }} - {{ $receipt->branch->name }}</td>
                        <td>{{ $receipt->supplier_name ?: 'Tidak dicantumkan' }}</td>
                        <td>{{ $receipt->items_count }} produk</td>
                        <td><strong>{{ \App\Support\Format\Rupiah::format($receipt->total_cost) }}</strong></td>
                        <td>{{ $receipt->creator?->name ?? 'Pengguna tidak tersedia' }}</td>
                        <td>{{ $receipt->created_at->locale('id')->translatedFormat('d F Y, H.i') }}</td>
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

    <div class="goods-receipts-list" aria-label="Daftar barang masuk versi mobile">
        @forelse ($receipts as $receipt)
            <article class="goods-receipt-card">
                <header class="goods-receipt-card__header">
                    <div>
                        <p class="goods-receipt-card__reference">{{ $receipt->receipt_number }}</p>
                        <time datetime="{{ $receipt->receipt_date->toDateString() }}">
                            {{ $receipt->receipt_date->locale('id')->translatedFormat('d F Y') }}
                        </time>
                    </div>
                    <span class="badge badge-success">Final</span>
                </header>

                <dl class="goods-receipt-card__body">
                    <div class="goods-receipt-card__row">
                        <dt class="goods-receipt-card__label">Cabang</dt>
                        <dd class="goods-receipt-card__value">
                            {{ $receipt->branch->code }} — {{ $receipt->branch->name }}
                        </dd>
                    </div>
                    <div class="goods-receipt-card__row">
                        <dt class="goods-receipt-card__label">Supplier</dt>
                        <dd class="goods-receipt-card__value">{{ $receipt->supplier_name ?: '—' }}</dd>
                    </div>
                    <div class="goods-receipt-card__row">
                        <dt class="goods-receipt-card__label">Jumlah Item</dt>
                        <dd class="goods-receipt-card__value">{{ $receipt->items_count }} produk</dd>
                    </div>
                    <div class="goods-receipt-card__row goods-receipt-card__row--total">
                        <dt class="goods-receipt-card__label">Total Biaya</dt>
                        <dd class="goods-receipt-card__value goods-receipt-card__total">
                            {{ \App\Support\Format\Rupiah::format($receipt->total_cost) }}
                        </dd>
                    </div>
                    <div class="goods-receipt-card__row">
                        <dt class="goods-receipt-card__label">Dicatat oleh</dt>
                        <dd class="goods-receipt-card__value">
                            {{ $receipt->creator?->name ?? 'Pengguna tidak tersedia' }}
                        </dd>
                    </div>
                    <div class="goods-receipt-card__row">
                        <dt class="goods-receipt-card__label">Waktu Input</dt>
                        <dd class="goods-receipt-card__value">
                            {{ $receipt->created_at->locale('id')->translatedFormat('d F Y · H.i') }}
                        </dd>
                    </div>
                </dl>

                <footer class="goods-receipt-card__footer">
                    <a class="btn btn-primary" href="{{ route('stock-receipts.show', $receipt) }}">
                        Detail Barang Masuk
                    </a>
                </footer>
            </article>
        @empty
            <div class="goods-receipts-empty" role="status">
                <h2>Barang Masuk tidak ditemukan</h2>
                <p>Belum ada data Barang Masuk atau data yang dicari tidak sesuai dengan filter.</p>
                <a class="btn btn-primary" href="{{ route('stock-receipts.create') }}">Tambah Barang Masuk</a>
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $receipts->firstItem() ?? 0 }}-{{ $receipts->lastItem() ?? 0 }} dari {{ $receipts->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination barang masuk">
            @if ($receipts->onFirstPage())
                <span class="pagination-button pagination-button--text" aria-disabled="true">Sebelumnya</span>
            @else
                <a class="pagination-button pagination-button--text" href="{{ $receipts->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $receipts->currentPage() }}</span>
            @if ($receipts->hasMorePages())
                <a class="pagination-button pagination-button--text" href="{{ $receipts->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="pagination-button pagination-button--text" aria-disabled="true">Berikutnya</span>
            @endif
        </nav>
    </div>
</section>
