<section class="card settings-section" id="aturan-bisnis">
    <div class="card__header settings-section__header">
        <div><span class="settings-section__number">04</span><h2>Aturan Bisnis</h2></div>
    </div>
    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.business.update') }}" data-settings-form>
        @csrf
        @method('PUT')
        <div class="business-rule-grid">
            <article class="business-rule-card">
                <h3>Stok Minimum Default</h3>
                <p>Hanya menjadi nilai awal pada produk baru dan tidak mengubah produk lama.</p>
                <label class="form-label" for="default_minimum_stock">Quantity</label>
                <input class="form-control @error('default_minimum_stock') is-error @enderror" id="default_minimum_stock" name="default_minimum_stock" type="text" inputmode="decimal" value="{{ \App\Support\Format\Quantity::inputValue(old('default_minimum_stock', $settings['business.default_minimum_stock'])) }}" required data-quantity-input>
                @error('default_minimum_stock')<span class="form-error">{{ $message }}</span>@enderror
            </article>
            <article class="business-rule-card">
                <h3>Batas Diskon Kasir</h3>
                <p>Batas nominal diskon per transaksi yang dapat diberikan Kasir.</p>
                <label class="form-label" for="maximum_cashier_discount">Nominal Rupiah</label>
                <div class="input-group">
                    <span class="input-group__addon">Rp</span>
                    <input class="form-control @error('maximum_cashier_discount') is-error @enderror" id="maximum_cashier_discount" name="maximum_cashier_discount" type="text" inputmode="numeric" autocomplete="off" value="{{ \App\Support\Format\Rupiah::input(old('maximum_cashier_discount', $settings['business.maximum_cashier_discount'])) }}" required data-rupiah-input>
                </div>
                @error('maximum_cashier_discount')<span class="form-error">{{ $message }}</span>@enderror
                <span class="form-help">Owner dan Admin tetap dapat memberi diskon sampai subtotal.</span>
            </article>
        </div>
        <div class="alert alert-warning">Perubahan batas diskon berlaku pada checkout berikutnya. Keranjang akan divalidasi ulang oleh server.</div>
        <div class="settings-save"><button class="btn btn-primary" type="submit">Simpan Aturan Bisnis</button></div>
    </form>
</section>
