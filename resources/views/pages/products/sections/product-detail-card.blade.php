<section class="product-detail-layout">
    <article class="card product-photo-card">
        <img src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}" alt="Foto {{ $product->name }}">
        @if($product->image_path)<button class="btn btn-danger btn-sm" type="button" data-remove-image data-action="{{ route('products.image.destroy', $product) }}" data-name="{{ $product->name }}">Hapus Foto</button>@endif
    </article>
    <article class="card detail-card"><dl class="detail-list">
        <div><dt>Kode</dt><dd>{{ $product->code }}</dd></div><div><dt>Barcode</dt><dd>{{ $product->barcode ?: '—' }}</dd></div><div><dt>Nama</dt><dd>{{ $product->name }}</dd></div><div><dt>Merek</dt><dd>{{ $product->brand ?: '—' }}</dd></div><div><dt>Ukuran</dt><dd>{{ $product->size ?: '—' }}</dd></div><div><dt>Kategori</dt><dd>{{ $product->category->name }}</dd></div><div><dt>Satuan</dt><dd>{{ $product->unit->name }}{{ $product->unit->symbol ? ' ('.$product->unit->symbol.')' : '' }}</dd></div>
        @if($isOwner)<div><dt>Harga Beli</dt><dd>{{ \App\Support\Format\Rupiah::format($product->purchase_price) }}</dd></div>@endif
        <div><dt>Harga Jual</dt><dd>{{ \App\Support\Format\Rupiah::format($product->selling_price) }}</dd></div><div><dt>Stok Minimum</dt><dd>{{ \App\Support\Format\Quantity::format($product->minimum_stock) }}</dd></div><div><dt>Status</dt><dd><span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div><div><dt>Dibuat Oleh</dt><dd>{{ $product->creator?->name ?: 'Sistem' }}</dd></div><div><dt>Diperbarui Oleh</dt><dd>{{ $product->updater?->name ?: 'Sistem' }}</dd></div><div><dt>Waktu Dibuat</dt><dd>{{ $product->created_at->format('d M Y H:i') }}</dd></div><div><dt>Waktu Diperbarui</dt><dd>{{ $product->updated_at->format('d M Y H:i') }}</dd></div>
    </dl></article>
</section>
