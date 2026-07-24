@extends('layouts.auth')

@section('title', 'Reset Kata Sandi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/reset-password.css') }}">
@endpush

@section('content')
    <header class="auth-heading">
        <p class="auth-heading__eyebrow">Pengaturan ulang</p>
        <h1>Buat kata sandi baru</h1>
        <p>Gunakan minimal delapan karakter dengan huruf besar, huruf kecil, dan angka.</p>
    </header>

    <form class="auth-form" action="{{ route('password.update') }}" method="POST">
        @csrf
        <input name="token" type="hidden" value="{{ $token }}">

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control @error('email') is-error @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                autocomplete="email"
                maxlength="255"
                required
                autofocus
            >
            @error('email')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Kata Sandi Baru</label>
            <div class="password-field">
                <input
                    class="form-control @error('password') is-error @enderror"
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Masukkan kata sandi baru"
                    autocomplete="new-password"
                    required
                >
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
                <input
                    class="form-control"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="Ulangi kata sandi baru"
                    autocomplete="new-password"
                    required
                >
                <button class="password-toggle" type="button" data-password-toggle data-password-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false">
                    <span data-password-toggle-label>Tampilkan</span>
                </button>
            </div>
        </div>

        <button class="btn btn-primary btn-lg auth-submit" type="submit">
            Reset Kata Sandi
        </button>
    </form>

    <div class="auth-back-link">
        <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
@endpush
