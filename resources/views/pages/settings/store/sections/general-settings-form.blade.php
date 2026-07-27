<section class="card settings-section" id="informasi-toko">
    <div class="card__header settings-section__header">
        <div><span class="settings-section__number">01</span><h2>Informasi Toko</h2></div>
        <span class="badge badge-info">Global</span>
    </div>
    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.general.update') }}" data-settings-form>
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group form-group--full">
                <label class="form-label" for="store_name">Nama Toko <span class="form-required">*</span></label>
                <input class="form-control @error('store_name') is-error @enderror" id="store_name" name="store_name" type="text" maxlength="150" required value="{{ old('store_name', $settings['store.name']) }}" data-preview-source="store-name">
                @error('store_name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group form-group--full">
                <label class="form-label" for="store_address">Alamat Toko</label>
                <textarea class="form-textarea @error('store_address') is-error @enderror" id="store_address" name="store_address" maxlength="1000" rows="3" data-preview-source="store-address">{{ old('store_address', $settings['store.address']) }}</textarea>
                @error('store_address')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="store_phone">Nomor Telepon</label>
                <input class="form-control @error('store_phone') is-error @enderror" id="store_phone" name="store_phone" type="tel" maxlength="30" value="{{ old('store_phone', $settings['store.phone']) }}" data-preview-source="store-phone">
                @error('store_phone')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <p class="settings-note">Alamat dan telepon cabang tetap diatur melalui Modul Cabang. Informasi toko ini digunakan sebagai data utama atau fallback pada struk.</p>
        <div class="settings-save"><button class="btn btn-primary" type="submit">Simpan Informasi Toko</button></div>
    </form>
</section>
