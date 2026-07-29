<section class="product-detail-layout">
    <article class="card product-photo-card">
        <img
            src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}"
            alt="Foto {{ $product->name }}"
        >
        @if ($product->image_path)
            <button
                class="btn btn-danger btn-sm"
                type="button"
                data-remove-image
                data-action="{{ route('products.image.destroy', $product) }}"
                data-name="{{ $product->name }}"
            >
                Hapus Foto
            </button>
        @endif
    </article>

    <article class="card detail-card product-detail-card">
        <section class="product-detail-group" aria-labelledby="product-identity-title">
            <h2 id="product-identity-title">Informasi Produk</h2>
            <dl class="detail-list">
                <div><dt>Nama</dt><dd>{{ $product->name }}</dd></div>
                <div><dt>Kode</dt><dd>{{ $product->code }}</dd></div>
                <div><dt>Barcode</dt><dd>{{ $product->barcode ?: '—' }}</dd></div>
                <div><dt>Merek</dt><dd>{{ $product->brand ?: '—' }}</dd></div>
                <div><dt>Ukuran/Kemasan</dt><dd>{{ $product->size ?: '—' }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $product->category->name }}</dd></div>
                <div><dt>Satuan</dt><dd>{{ $product->unit->name }}{{ $product->unit->symbol ? ' ('.$product->unit->symbol.')' : '' }}</dd></div>
            </dl>
        </section>

        <section class="product-detail-group" aria-labelledby="product-price-title">
            <h2 id="product-price-title">Harga dan Peringatan Stok</h2>
            <dl class="detail-list">
                @if ($isOwner)
                    <div>
                        <dt>Harga Beli</dt>
                        <dd class="product-detail-price">{{ \App\Support\Format\Rupiah::format($product->purchase_price) }}</dd>
                    </div>
                @endif
                <div>
                    <dt>Harga Jual</dt>
                    <dd class="product-detail-price">{{ \App\Support\Format\Rupiah::format($product->selling_price) }}</dd>
                </div>
                <div>
                    <dt>Stok Minimum</dt>
                    <dd>
                        {{ \App\Support\Format\Quantity::format($product->minimum_stock) }}
                        {{ $product->unit->symbol ?: $product->unit->name }}
                    </dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>
                        <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </div>
            </dl>
            <p class="product-detail-note">
                Harga jual berlaku untuk transaksi baru di seluruh cabang. Riwayat transaksi lama tetap menggunakan harga tersimpan.
            </p>
        </section>

        <section class="product-detail-group" aria-labelledby="product-audit-title">
            <h2 id="product-audit-title">Informasi Perubahan</h2>
            <dl class="detail-list">
                <div><dt>Dibuat Oleh</dt><dd>{{ $product->creator?->name ?: 'Sistem' }}</dd></div>
                <div><dt>Diperbarui Oleh</dt><dd>{{ $product->updater?->name ?: 'Sistem' }}</dd></div>
                <div><dt>Waktu Dibuat</dt><dd>{{ $product->created_at->format('d M Y H:i') }}</dd></div>
                <div><dt>Waktu Diperbarui</dt><dd>{{ $product->updated_at->format('d M Y H:i') }}</dd></div>
            </dl>
        </section>
    </article>
</section>
