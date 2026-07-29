@php
    $hasProductFilters = $search !== ''
        || $categoryFilter !== null
        || $unitFilter !== null
        || $status !== null;
@endphp

<section class="table-card products-page__table-card" aria-label="Daftar produk"><div class="table-wrapper products-page__desktop-table"><table class="table product-table">
    <thead><tr><th>Produk</th><th>Identitas</th><th>Kategori</th><th>Satuan</th>@if($isOwner)<th>Harga Beli</th>@endif<th>Harga Jual</th><th>Status</th><th>Diperbarui</th><th class="table-actions">Aksi</th></tr></thead>
    <tbody>@forelse($products as $product)<tr>
        <td><div class="product-cell"><img class="product-thumbnail" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}" alt=""><span><strong>{{ $product->name }}</strong><small>{{ $product->brand ?: 'Tanpa merek' }}{{ $product->size ? ' · '.$product->size : '' }}</small></span></div></td>
        <td><strong>{{ $product->code }}</strong><small class="table-secondary">{{ $product->barcode ?: 'Tanpa barcode' }}</small></td><td>{{ $product->category->name }}</td><td>{{ $product->unit->symbol ?: $product->unit->name }}</td>
        @if($isOwner)<td>{{ \App\Support\Format\Rupiah::format($product->purchase_price) }}</td>@endif
        <td><strong>{{ \App\Support\Format\Rupiah::format($product->selling_price) }}</strong></td><td><span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $product->updated_at->format('d M Y') }}</td>
        <td class="table-actions"><div class="action-group"><a class="btn btn-sm btn-outline" href="{{ route('products.show', $product) }}">Detail</a><a class="btn btn-sm btn-secondary" href="{{ route('products.edit', $product) }}">Edit</a><a class="btn btn-sm btn-outline" href="{{ route('products.price-history.index', $product) }}">Riwayat</a><button class="btn btn-sm {{ $product->is_active ? 'btn-danger' : 'btn-success' }}" type="button" data-product-status data-action="{{ route('products.status.update', $product) }}" data-name="{{ $product->name }}" data-next-status="{{ $product->is_active ? '0' : '1' }}">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></div></td>
    </tr>@empty<tr class="table-empty-row"><td colspan="{{ $isOwner ? 9 : 8 }}">Tidak ada produk yang sesuai dengan filter.</td></tr>@endforelse</tbody>
</table></div>

<div class="products-list" aria-label="Daftar produk mobile">
    @forelse ($products as $product)
        <article class="product-card">
            <header class="product-card__header">
                <img class="product-card__image" src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}" alt="">
                <div class="product-card__identity">
                    <strong class="product-card__name">{{ $product->name }}</strong>
                    <span class="product-card__code">Kode: {{ $product->code }}</span>
                    <span class="product-card__code">Barcode: {{ $product->barcode ?: '—' }}</span>
                </div>
                <span class="badge product-card__status {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </header>
            <dl class="product-card__body">
                <div class="product-card__row">
                    <dt class="product-card__label">Kategori</dt>
                    <dd class="product-card__value">{{ $product->category->name }}</dd>
                </div>
                <div class="product-card__row">
                    <dt class="product-card__label">Satuan</dt>
                    <dd class="product-card__value">{{ $product->unit->name }}{{ $product->unit->symbol ? ' ('.$product->unit->symbol.')' : '' }}</dd>
                </div>
                <div class="product-card__row">
                    <dt class="product-card__label">Merek</dt>
                    <dd class="product-card__value">{{ $product->brand ?: '—' }}</dd>
                </div>
                <div class="product-card__row">
                    <dt class="product-card__label">Ukuran/Kemasan</dt>
                    <dd class="product-card__value">{{ $product->size ?: '—' }}</dd>
                </div>
                <div class="product-card__row product-card__row--price">
                    <dt class="product-card__label">Harga Jual</dt>
                    <dd class="product-card__value product-card__price">{{ \App\Support\Format\Rupiah::format($product->selling_price) }}</dd>
                </div>
                <div class="product-card__row">
                    <dt class="product-card__label">Stok Minimum</dt>
                    <dd class="product-card__value">
                        {{ \App\Support\Format\Quantity::format($product->minimum_stock) }}
                        {{ $product->unit->symbol ?: $product->unit->name }}
                    </dd>
                </div>
            </dl>
            <footer class="product-card__footer">
                <a class="btn btn-outline" href="{{ route('products.show', $product) }}">Detail</a>
                <details class="product-card__actions" data-master-action-menu>
                    <summary class="btn btn-secondary product-card__action-toggle" aria-expanded="false">Tindakan</summary>
                    <div class="product-card__action-menu" role="menu">
                        <a class="btn btn-secondary" href="{{ route('products.edit', $product) }}" role="menuitem">Ubah Produk</a>
                        <a class="btn btn-outline" href="{{ route('products.edit', $product) }}#selling_price" role="menuitem">Ubah Harga</a>
                        <a class="btn btn-outline" href="{{ route('products.price-history.index', $product) }}" role="menuitem">Riwayat Harga</a>
                        <button class="btn {{ $product->is_active ? 'btn-danger' : 'btn-success' }}" type="button" role="menuitem" data-product-status data-action="{{ route('products.status.update', $product) }}" data-name="{{ $product->name }}" data-next-status="{{ $product->is_active ? '0' : '1' }}">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </div>
                </details>
            </footer>
        </article>
    @empty
        <div class="products-empty">
            <h3>{{ $hasProductFilters ? 'Produk yang dicari tidak ditemukan' : 'Belum ada produk' }}</h3>
            <p>{{ $hasProductFilters ? 'Ubah pencarian atau reset filter untuk melihat produk lain.' : 'Tambahkan produk pertama untuk mulai mengelola data produk.' }}</p>
            @if ($hasProductFilters)
                <a class="btn btn-secondary" href="{{ route('products.index') }}">Reset Filter</a>
            @else
                @can('create', \App\Models\Product::class)
                    <a class="btn btn-primary" href="{{ route('products.create') }}">Tambah Produk</a>
                @endcan
            @endif
        </div>
    @endforelse
</div>

<div class="table-pagination products-page__pagination"><span>Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }}</span><nav class="pagination-buttons" aria-label="Pagination produk">@if($products->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $products->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif<span class="pagination-button is-active" aria-current="page">{{ $products->currentPage() }}</span>@if($products->hasMorePages())<a class="pagination-button" href="{{ $products->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif</nav></div></section>
