@php
    $statusLabels = ['safe' => 'Aman', 'low' => 'Menipis', 'out' => 'Habis'];
    $statusBadges = ['safe' => 'badge-success', 'low' => 'badge-warning', 'out' => 'badge-danger'];
@endphp

<section class="table-card" aria-label="Daftar stok produk">
    <div class="table-wrapper">
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
    <div class="table-pagination">
        <span>Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination stok">
            @if ($products->onFirstPage())
                <span class="pagination-button" aria-disabled="true">‹</span>
            @else
                <a class="pagination-button" href="{{ $products->previousPageUrl() }}">‹</a>
            @endif
            <span class="pagination-button is-active">{{ $products->currentPage() }}</span>
            @if ($products->hasMorePages())
                <a class="pagination-button" href="{{ $products->nextPageUrl() }}">›</a>
            @else
                <span class="pagination-button" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
</section>
