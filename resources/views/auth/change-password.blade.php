@extends('layouts.app')

@section('title', 'Ubah Kata Sandi')
@section('page-title', 'Ubah Kata Sandi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/change-password.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Ubah Kata Sandi Owner',
        'description' => 'Perbarui kata sandi akun Owner yang sedang digunakan.',
        'eyebrow' => 'Khusus Owner',
        'breadcrumbs' => [
            ['label' => 'Akun Saya', 'url' => route('account.index')],
            ['label' => 'Ubah Kata Sandi'],
        ],
    ])

    <section class="card password-card" aria-labelledby="password-form-title">
        <div class="password-card__heading">
            <h3 id="password-form-title">Kata sandi akun Owner</h3>
            <p>Untuk akun Admin atau Kasir, gunakan tindakan Reset Kata Sandi pada modul Pengguna.</p>
        </div>

        <form class="password-form" action="{{ route('account.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="current_password">Kata Sandi Owner Saat Ini</label>
                <div class="password-field">
                    <input class="form-control @error('current_password') is-error @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="current_password" aria-controls="current_password" aria-label="Tampilkan kata sandi Owner saat ini" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
                @error('current_password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
                <span class="form-help">Diperlukan untuk mengonfirmasi bahwa perubahan dilakukan oleh Owner.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi Baru</label>
                <div class="password-field">
                    <input class="form-control @error('password') is-error @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="password" aria-controls="password" aria-label="Tampilkan kata sandi baru" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <div class="password-field">
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
            </div>

            <div class="password-form__actions">
                <a class="btn btn-secondary" href="{{ route('account.index') }}">Batal</a>
                <button class="btn btn-primary" type="submit">Simpan Kata Sandi</button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
@endpush
