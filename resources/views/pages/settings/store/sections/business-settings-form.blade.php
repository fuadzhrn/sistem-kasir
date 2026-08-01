@php($businessHasErrors = $errors->hasAny(['default_minimum_stock', 'maximum_cashier_discount']))

<section class="card settings-section" id="aturan-bisnis">
    <div class="card__header settings-section__header">
        <div class="settings-section__heading">
            <span class="settings-section__number">04</span>
            <div>
                <h2>Aturan Bisnis</h2>
                <p>Nilai awal stok produk baru dan batas diskon transaksi berikutnya.</p>
            </div>
        </div>
        @if ($businessHasErrors)
            <span class="badge badge-danger" role="status">Periksa input</span>
        @endif
    </div>

    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.business.update') }}" data-settings-form>
        @csrf
        @method('PUT')

        <div class="business-rule-grid">
            <article class="business-rule-card">
                <h3>Stok Minimum Default</h3>
                <p>Nilai ini digunakan sebagai nilai awal untuk produk baru. Produk lama tetap mengikuti stok minimum yang sudah tersimpan.</p>
                <label class="form-label" for="default_minimum_stock">Quantity <span class="form-required">*</span></label>
                <input
                    class="form-control @error('default_minimum_stock') is-error @enderror"
                    id="default_minimum_stock"
                    name="default_minimum_stock"
                    type="text"
                    inputmode="decimal"
                    value="{{ \App\Support\Format\Quantity::inputValue(old('default_minimum_stock', $settings['business.default_minimum_stock'])) }}"
                    required
                    data-quantity-input
                    @error('default_minimum_stock') aria-invalid="true" aria-describedby="default_minimum_stock_error" @enderror
                >
                @error('default_minimum_stock')<span class="form-error" id="default_minimum_stock_error">{{ $message }}</span>@enderror
            </article>

            <article class="business-rule-card">
                <h3>Batas Diskon Kasir</h3>
                <p>Batas nominal diskon per transaksi yang dapat diberikan Kasir.</p>
                <label class="form-label" for="maximum_cashier_discount">Nominal Rupiah <span class="form-required">*</span></label>
                <div class="input-group">
                    <span class="input-group__addon">Rp</span>
                    <input
                        class="form-control @error('maximum_cashier_discount') is-error @enderror"
                        id="maximum_cashier_discount"
                        name="maximum_cashier_discount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        value="{{ \App\Support\Format\Rupiah::input(old('maximum_cashier_discount', $settings['business.maximum_cashier_discount'])) }}"
                        required
                        data-rupiah-input
                        aria-describedby="maximum_cashier_discount_help @error('maximum_cashier_discount') maximum_cashier_discount_error @enderror"
                        @error('maximum_cashier_discount') aria-invalid="true" @enderror
                    >
                </div>
                @error('maximum_cashier_discount')<span class="form-error" id="maximum_cashier_discount_error">{{ $message }}</span>@enderror
                <span class="form-help" id="maximum_cashier_discount_help">Batas berlaku untuk transaksi baru dan tetap divalidasi oleh sistem. Owner dan Admin tetap dapat memberi diskon sampai subtotal.</span>
            </article>
        </div>

        <div class="alert alert-warning">Perubahan batas diskon berlaku pada checkout berikutnya. Keranjang akan divalidasi ulang oleh server.</div>

        <div class="settings-save">
            <button class="btn btn-primary" type="submit" data-settings-submit data-default-label="Simpan Pengaturan">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</section>
