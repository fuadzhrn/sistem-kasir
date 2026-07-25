<div class="empty-state cashier-product-empty" data-product-empty @if ($branch !== null) hidden @endif>
    <div class="empty-state__icon" aria-hidden="true">PR</div>
    <h3>{{ $branch === null ? 'Pilih cabang terlebih dahulu' : 'Produk tidak ditemukan' }}</h3>
    <p data-product-empty-message>{{ $branch === null ? 'Produk baru dimuat setelah Owner memilih cabang aktif.' : 'Ubah kata pencarian atau kategori untuk melihat hasil lain.' }}</p>
</div>
