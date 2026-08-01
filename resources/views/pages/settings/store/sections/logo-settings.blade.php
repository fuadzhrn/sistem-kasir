@php($logoHasErrors = $errors->has('logo'))

<section class="card settings-section" id="logo-toko">
    <div class="card__header settings-section__header">
        <div class="settings-section__heading">
            <span class="settings-section__number">02</span>
            <div>
                <h2>Logo Toko</h2>
                <p>Lihat logo saat ini dan pratinjau file baru sebelum disimpan.</p>
            </div>
        </div>
        @if ($logoHasErrors)
            <span class="badge badge-danger" role="status">Periksa file</span>
        @endif
    </div>

    <div class="card__body logo-settings">
        <div class="logo-settings__preview" data-logo-current aria-live="polite">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $settings['store.name'] }}" data-logo-preview>
            @else
                <div class="logo-settings__empty" data-logo-empty>Belum ada logo toko</div>
                <img src="" alt="Pratinjau logo baru" data-logo-preview hidden>
            @endif
        </div>

        <form method="POST" action="{{ route('settings.store.logo.update') }}" enctype="multipart/form-data" data-settings-form data-logo-upload-form>
            @csrf
            <div class="form-group">
                <label class="form-label" for="logo">Pilih Logo Baru</label>
                <input
                    class="form-file @error('logo') is-error @enderror"
                    id="logo"
                    name="logo"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    required
                    data-logo-input
                    aria-describedby="logo_help logo_file_name @error('logo') logo_error @enderror"
                    @error('logo') aria-invalid="true" @enderror
                >
                <span class="logo-settings__file-name" id="logo_file_name" data-logo-file-name>Belum ada file baru dipilih.</span>
                <span class="form-help" id="logo_help">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB, dimensi 100–3000 piksel.</span>
                @error('logo')<span class="form-error" id="logo_error">{{ $message }}</span>@enderror
            </div>

            <div class="settings-save">
                <button class="btn btn-primary" type="submit" data-settings-submit data-default-label="Simpan Logo">
                    Simpan Logo
                </button>
            </div>
        </form>

        @if ($logoUrl)
            <form method="POST" action="{{ route('settings.store.logo.destroy') }}" data-logo-delete-form>
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit" data-settings-submit data-default-label="Hapus Logo">Hapus Logo</button>
            </form>
        @endif
    </div>
</section>
