<div class="product-form-grid">
    @if ($editing)
        <aside class="product-current-price product-form-grid__wide" aria-label="Harga produk saat ini">
            <div>
                <span>Produk</span>
                <strong>{{ $product->name }}</strong>
            </div>
            @if ($isOwner)
                <div>
                    <span>Harga Beli Saat Ini</span>
                    <strong>{{ \App\Support\Format\Rupiah::format($product->purchase_price) }}</strong>
                </div>
            @endif
            <div>
                <span>Harga Jual Saat Ini</span>
                <strong>{{ \App\Support\Format\Rupiah::format($product->selling_price) }}</strong>
            </div>
        </aside>
    @endif

    @if($isOwner)
        <div class="form-group"><label class="form-label" for="purchase_price">{{ $editing ? 'Harga Beli Baru' : 'Harga Beli' }} *</label><div class="money-input"><span>Rp</span><input class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" type="text" inputmode="numeric" autocomplete="off" required value="{{ \App\Support\Format\Rupiah::input(old('purchase_price', $product->purchase_price ?? '0.00')) }}" data-rupiah-input></div>@error('purchase_price')<span class="form-error">{{ $message }}</span>@enderror</div>
    @endif
    <div class="form-group"><label class="form-label" for="selling_price">{{ $editing ? 'Harga Jual Baru' : 'Harga Jual' }} *</label><div class="money-input"><span>Rp</span><input class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" type="text" inputmode="numeric" autocomplete="off" required value="{{ \App\Support\Format\Rupiah::input(old('selling_price', $product->selling_price ?? '0.00')) }}" data-rupiah-input></div>@error('selling_price')<span class="form-error">{{ $message }}</span>@enderror</div>
    <div class="form-group"><label class="form-label" for="minimum_stock">Stok Minimum *</label><input class="form-control @error('minimum_stock') is-invalid @enderror" id="minimum_stock" name="minimum_stock" type="text" inputmode="decimal" required value="{{ \App\Support\Format\Quantity::inputValue(old('minimum_stock', $product->minimum_stock ?? $defaultMinimumStock ?? '0.000')) }}" data-quantity-input><small class="form-help">Maksimal tiga desimal; gunakan koma atau titik. Pada produk baru menggunakan default toko.</small>@error('minimum_stock')<span class="form-error">{{ $message }}</span>@enderror</div>
    @if($editing)
        <div class="form-group product-form-grid__wide"><label class="form-label" for="price_change_reason">Alasan Perubahan Harga</label><textarea class="form-control @error('price_change_reason') is-invalid @enderror" id="price_change_reason" name="price_change_reason" rows="3" maxlength="500" placeholder="Opsional; dicatat jika harga berubah">{{ old('price_change_reason') }}</textarea>@error('price_change_reason')<span class="form-error">{{ $message }}</span>@enderror</div>
        <div class="alert alert-info product-global-price-note product-form-grid__wide" role="note">
            Harga baru akan berlaku untuk transaksi baru di seluruh cabang. Transaksi lama tidak berubah.
        </div>
    @endif
</div>
