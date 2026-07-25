<section class="card form-card">
    <form action="{{ $action }}" method="POST" data-user-form>
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap <span class="form-required">*</span></label>
                <input class="form-control @error('name') is-error @enderror" id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" maxlength="255" required>
                @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="username">Username <span class="form-required">*</span></label>
                <input class="form-control @error('username') is-error @enderror" id="username" name="username" type="text" value="{{ old('username', $user?->username) }}" maxlength="255" autocomplete="off" required>
                @error('username')<span class="form-error">{{ $message }}</span>@enderror
                <span class="form-help">Huruf kecil, angka, titik, garis bawah, atau tanda hubung.</span>
            </div>
            <div class="form-group form-group--full">
                <label class="form-label" for="email">Email</label>
                <input class="form-control @error('email') is-error @enderror" id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" maxlength="255">
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            @include('pages.users.sections.role-branch-fields')

            @if ($showPassword)
                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi <span class="form-required">*</span></label>
                    <div class="password-field">
                        <input class="form-control @error('password') is-error @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="password" aria-controls="password" aria-label="Tampilkan kata sandi"><span data-password-toggle-label>Tampilkan</span></button>
                    </div>
                    @error('password')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi <span class="form-required">*</span></label>
                    <div class="password-field">
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi"><span data-password-toggle-label>Tampilkan</span></button>
                    </div>
                </div>
            @endif
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ $user ? route('users.show', $user) : route('users.index') }}">Batal</a>
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
        </div>
    </form>
</section>
