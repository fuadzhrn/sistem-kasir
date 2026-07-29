@php
    $statusLabels = ['safe' => 'Aman', 'low' => 'Menipis', 'out' => 'Habis'];
    $statusBadges = ['safe' => 'badge-success', 'low' => 'badge-warning', 'out' => 'badge-danger'];
@endphp

<section class="table-card" aria-label="Daftar stok produk">
    <div class="table-wrapper inventory-desktop-table">
        <table class="table stock-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Identitas</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="table-number">Quantity</th>
                    <th class="table-number">Stok Minimum</th>
                    <th>Status</th>
                    <th>Pembaruan</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="stock-product">
                                <img
                                    class="stock-product__image"
                                    src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}"
                                    alt=""
                                >
                                <span>
                                    <strong>{{ $product->name }}</strong>
                                    <small>{{ $product->brand ?: 'Tanpa merek' }}{{ $product->size ? ' · '.$product->size : '' }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $product->code }}</strong>
                            <small class="table-secondary">{{ $product->barcode ?: 'Tanpa barcode' }}</small>
                        </td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->unit->symbol ?: $product->unit->name }}</td>
                        <td class="table-number stock-quantity">
                            {{ \App\Support\Format\Quantity::format($product->stock_quantity) }}
                        </td>
                        <td class="table-number">{{ \App\Support\Format\Quantity::format($product->minimum_stock) }}</td>
                        <td>
                            <span class="badge {{ $statusBadges[$product->stock_status] }}">
                                {{ $statusLabels[$product->stock_status] }}
                            </span>
                        </td>
                        <td>
                            {{ $product->stock_updated_at
                                ? \Illuminate\Support\Carbon::parse($product->stock_updated_at)->format('d M Y, H:i')
                                : 'Belum diinput' }}
                        </td>
                        <td class="table-actions">
                            <div class="action-group">
                                @if ($product->branch_stock_id)
                                    <a class="btn btn-sm btn-outline" href="{{ route('stocks.show', $product->branch_stock_id) }}">Detail</a>
                                @endif
                                <a
                                    class="btn btn-sm btn-secondary"
                                    href="{{ route('stocks.initial.create', auth()->user()->isOwner()
                                        ? ['branch_id' => $selectedBranch->id, 'product_id' => $product->id]
                                        : ['product_id' => $product->id]) }}"
                                >
                                    {{ $product->branch_stock_id ? 'Koreksi Awal' : 'Input Awal' }}
                                </a>
                                <a
                                    class="btn btn-sm btn-ghost"
                                    href="{{ route('stocks.history.index', auth()->user()->isOwner()
                                        ? ['branch_id' => $selectedBranch->id, 'product_id' => $product->id]
                                        : ['product_id' => $product->id]) }}"
                                >
                                    Riwayat
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="9">Tidak ada produk yang sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="inventory-list" aria-label="Daftar stok produk versi mobile">
        @forelse ($products as $product)
            <article class="inventory-card">
                <header class="inventory-card__header">
                    <div class="inventory-card__heading">
                        <p class="inventory-card__branch">{{ $selectedBranch->name }}</p>
                        <h2 class="inventory-card__product">{{ $product->name }}</h2>
                        <p class="inventory-card__code">Kode: {{ $product->code }}</p>
                    </div>
                    <span class="badge {{ $statusBadges[$product->stock_status] }}">
                        {{ $statusLabels[$product->stock_status] }}
                    </span>
                </header>

                <dl class="inventory-card__body">
                    <div class="inventory-card__row">
                        <dt class="inventory-card__label">Kategori</dt>
                        <dd class="inventory-card__value">{{ $product->category->name }}</dd>
                    </div>
                    <div class="inventory-card__row">
                        <dt class="inventory-card__label">Satuan</dt>
                        <dd class="inventory-card__value">{{ $product->unit->symbol ?: $product->unit->name }}</dd>
                    </div>
                    <div class="inventory-card__row inventory-card__row--quantity">
                        <dt class="inventory-card__label">Stok Tersedia</dt>
                        <dd class="inventory-card__value inventory-card__quantity">
                            {{ \App\Support\Format\Quantity::format($product->stock_quantity) }}
                            {{ $product->unit->symbol ?: $product->unit->name }}
                        </dd>
                    </div>
                    <div class="inventory-card__row">
                        <dt class="inventory-card__label">Stok Minimum</dt>
                        <dd class="inventory-card__value">
                            {{ \App\Support\Format\Quantity::format($product->minimum_stock) }}
                            {{ $product->unit->symbol ?: $product->unit->name }}
                        </dd>
                    </div>
                    <div class="inventory-card__row">
                        <dt class="inventory-card__label">Pembaruan</dt>
                        <dd class="inventory-card__value">
                            {{ $product->stock_updated_at
                                ? \Illuminate\Support\Carbon::parse($product->stock_updated_at)->format('d M Y, H:i')
                                : 'Belum diinput' }}
                        </dd>
                    </div>
                </dl>

                <footer class="inventory-card__footer">
                    @if ($product->branch_stock_id)
                        <a class="btn btn-primary" href="{{ route('stocks.show', $product->branch_stock_id) }}">
                            Detail Pergerakan
                        </a>
                    @else
                        <a
                            class="btn btn-primary"
                            href="{{ route('stocks.history.index', auth()->user()->isOwner()
                                ? ['branch_id' => $selectedBranch->id, 'product_id' => $product->id]
                                : ['product_id' => $product->id]) }}"
                        >
                            Riwayat Pergerakan
                        </a>
                    @endif
                    <a
                        class="btn btn-secondary"
                        href="{{ route('stocks.initial.create', auth()->user()->isOwner()
                            ? ['branch_id' => $selectedBranch->id, 'product_id' => $product->id]
                            : ['product_id' => $product->id]) }}"
                    >
                        {{ $product->branch_stock_id ? 'Koreksi Awal' : 'Input Stok Awal' }}
                    </a>
                </footer>
            </article>
        @empty
            <div class="inventory-empty" role="status">
                <h2>Data stok tidak ditemukan</h2>
                <p>Belum ada data stok pada cabang ini atau produk yang dicari tidak sesuai dengan filter.</p>
                <a
                    class="btn btn-primary"
                    href="{{ route('stocks.initial.create', auth()->user()->isOwner() ? ['branch_id' => $selectedBranch->id] : []) }}"
                >
                    Catat Stok Awal
                </a>
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination stok">
            @if ($products->onFirstPage())
                <span class="pagination-button pagination-button--text" aria-disabled="true">Sebelumnya</span>
            @else
                <a class="pagination-button pagination-button--text" href="{{ $products->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $products->currentPage() }}</span>
            @if ($products->hasMorePages())
                <a class="pagination-button pagination-button--text" href="{{ $products->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="pagination-button pagination-button--text" aria-disabled="true">Berikutnya</span>
            @endif
        </nav>
    </div>
</section>
