@php
    $oldItems = old('items', [['product_id' => '', 'quantity' => '', 'purchase_price' => '']]);
@endphp

<section class="card receipt-items-card">
    <div class="card__header receipt-items-card__header">
        <div>
            <p class="eyebrow">Rincian produk</p>
            <h3>Item Barang Masuk</h3>
        </div>
        <button class="btn btn-secondary" type="button" data-add-receipt-item>Tambah Item Produk</button>
    </div>
    <div class="receipt-product-search">
        <label class="form-label" for="receipt-product-search">Pencarian produk</label>
        <input class="form-control" id="receipt-product-search" type="search" placeholder="Ketik kode, nama, merek, atau ukuran produk" data-product-search>
        <span class="form-help">Pencarian menyaring pilihan produk pada setiap baris.</span>
    </div>
    @error('items')<div class="alert alert-danger receipt-items-error">{{ $message }}</div>@enderror
    @if ($products->isEmpty())
        <div class="goods-receipts-empty goods-receipts-empty--inline" role="alert">
            <h4>Produk aktif belum tersedia</h4>
            <p>Barang Masuk belum dapat disimpan sebelum terdapat produk aktif.</p>
        </div>
    @endif
    <div class="table-wrapper receipt-items-table-wrapper">
        <table class="table receipt-items-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kode</th>
                    <th>Ukuran</th>
                    <th>Satuan</th>
                    <th>Quantity</th>
                    <th>Harga beli</th>
                    <th>Subtotal</th>
                    <th aria-label="Hapus baris"></th>
                </tr>
            </thead>
            <tbody data-receipt-items>
                @foreach ($oldItems as $rowIndex => $item)
                    @include('pages.stock-receipts.sections.receipt-item-row', ['rowIndex' => $rowIndex, 'item' => $item])
                @endforeach
            </tbody>
        </table>
    </div>
    <template data-receipt-item-template>
        @include('pages.stock-receipts.sections.receipt-item-row', [
            'rowIndex' => '__INDEX__',
            'item' => ['product_id' => '', 'quantity' => '', 'purchase_price' => ''],
        ])
    </template>
    <div class="card__footer">Minimal 1 dan maksimal 100 jenis produk. Produk yang sama tidak boleh dipilih dua kali.</div>
</section>
