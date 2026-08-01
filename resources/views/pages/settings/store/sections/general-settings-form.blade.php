@php($generalHasErrors = $errors->hasAny(['store_name', 'store_address', 'store_phone']))

<section class="card settings-section" id="informasi-toko">
    <div class="card__header settings-section__header">
        <div class="settings-section__heading">
            <span class="settings-section__number">01</span>
            <div>
                <h2>Identitas dan Kontak Toko</h2>
                <p>Informasi utama yang digunakan sebagai identitas dan fallback pada struk.</p>
            </div>
        </div>
        @if ($generalHasErrors)
            <span class="badge badge-danger" role="status">Periksa input</span>
        @else
            <span class="badge badge-info">Global</span>
        @endif
    </div>

    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.general.update') }}" data-settings-form>
        @csrf
        @method('PUT')

        <fieldset class="settings-subsection">
            <legend>Identitas Toko</legend>
            <p class="settings-subsection__description">Nama dan alamat toko tetap ditampilkan dari nilai yang sudah tersimpan.</p>

            <div class="form-group">
                <label class="form-label" for="store_name">Nama Toko <span class="form-required">*</span></label>
                <input
                    class="form-control @error('store_name') is-error @enderror"
                    id="store_name"
                    name="store_name"
                    type="text"
                    maxlength="150"
                    required
                    value="{{ old('store_name', $settings['store.name']) }}"
                    data-preview-source="store-name"
                    @error('store_name') aria-invalid="true" aria-describedby="store_name_error" @enderror
                >
                @error('store_name')<span class="form-error" id="store_name_error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="store_address">Alamat Toko</label>
                <textarea
                    class="form-textarea @error('store_address') is-error @enderror"
                    id="store_address"
                    name="store_address"
                    maxlength="1000"
                    rows="3"
                    data-preview-source="store-address"
                    @error('store_address') aria-invalid="true" aria-describedby="store_address_error" @enderror
                >{{ old('store_address', $settings['store.address']) }}</textarea>
                @error('store_address')<span class="form-error" id="store_address_error">{{ $message }}</span>@enderror
            </div>
        </fieldset>

        <fieldset class="settings-subsection">
            <legend>Kontak Toko</legend>
            <p class="settings-subsection__description">Nomor telepon boleh menggunakan tanda tambah, spasi, atau tanda hubung.</p>

            <div class="form-group">
                <label class="form-label" for="store_phone">Nomor Telepon</label>
                <input
                    class="form-control @error('store_phone') is-error @enderror"
                    id="store_phone"
                    name="store_phone"
                    type="tel"
                    inputmode="tel"
                    maxlength="30"
                    value="{{ old('store_phone', $settings['store.phone']) }}"
                    data-preview-source="store-phone"
                    @error('store_phone') aria-invalid="true" aria-describedby="store_phone_error" @enderror
                >
                @error('store_phone')<span class="form-error" id="store_phone_error">{{ $message }}</span>@enderror
            </div>
        </fieldset>

        <p class="settings-note">Alamat dan telepon cabang tetap diatur melalui Modul Cabang. Informasi toko ini digunakan sebagai data utama atau fallback pada struk.</p>

        <div class="settings-save">
            <button class="btn btn-primary" type="submit" data-settings-submit data-default-label="Simpan Pengaturan">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</section>
