<section class="card settings-section" id="logo-toko">
    <div class="card__header settings-section__header">
        <div><span class="settings-section__number">02</span><h2>Logo Toko</h2></div>
    </div>
    <div class="card__body logo-settings">
        <div class="logo-settings__preview" data-logo-current>
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $settings['store.name'] }}" data-logo-preview>
            @else
                <div class="logo-settings__empty" data-logo-empty>Belum ada logo toko</div>
                <img src="" alt="Preview logo baru" data-logo-preview hidden>
            @endif
        </div>
        <form method="POST" action="{{ route('settings.store.logo.update') }}" enctype="multipart/form-data" data-settings-form data-logo-upload-form>
            @csrf
            <div class="form-group">
                <label class="form-label" for="logo">Pilih Logo Baru</label>
                <input class="form-file @error('logo') is-error @enderror" id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-logo-input>
                <span class="form-help">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB, dimensi 100–3000 piksel.</span>
                @error('logo')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="settings-save"><button class="btn btn-primary" type="submit">Simpan Logo</button></div>
        </form>
        @if ($logoUrl)
            <form method="POST" action="{{ route('settings.store.logo.destroy') }}" data-logo-delete-form>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Hapus Logo</button>
            </form>
        @endif
    </div>
</section>
