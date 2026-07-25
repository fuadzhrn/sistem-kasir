@php($editing = isset($product))
<div class="product-form-sections">
    <section class="product-form-section">
        <div class="product-form-section__header"><span>01</span><div><h3>Informasi Produk</h3><p>Identitas dasar yang digunakan seluruh cabang.</p></div></div>
        @include('pages.products.sections.product-identity-fields')
    </section>
    <section class="product-form-section">
        <div class="product-form-section__header"><span>02</span><div><h3>Harga dan Peringatan Stok</h3><p>Harga jual bersifat global. Stok minimum tidak mengubah stok cabang.</p></div></div>
        @include('pages.products.sections.product-price-fields')
    </section>
    <section class="product-form-section">
        <div class="product-form-section__header"><span>03</span><div><h3>Foto Produk</h3><p>Foto bersifat opsional dan disimpan pada storage publik.</p></div></div>
        @include('pages.products.sections.product-image-field')
    </section>
</div>
<div class="form-actions"><a class="btn btn-secondary" href="{{ $editing ? route('products.show', $product) : route('products.index') }}">Batal</a><button class="btn btn-primary" type="submit" data-product-submit>{{ $editing ? 'Simpan Perubahan' : 'Tambah Produk' }}</button></div>
