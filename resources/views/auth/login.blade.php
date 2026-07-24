@extends('layouts.auth')

@section('title', 'Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/login.css') }}">
@endpush

@section('content')
    <header class="auth-heading">
        <p class="auth-heading__eyebrow">Selamat datang kembali</p>
        <h1>Masuk ke akun Anda</h1>
        <p>Kelola transaksi, stok, dan laporan toko dalam satu sistem.</p>
    </header>

    <form class="auth-form" action="{{ route('login.store') }}" method="POST" data-login-form>
        @csrf

        <div class="form-group">
            <label class="form-label" for="login">Username atau Email</label>
            <input
                class="form-control @error('login') is-error @enderror"
                id="login"
                name="login"
                type="text"
                value="{{ old('login') }}"
                placeholder="Masukkan username atau email"
                autocomplete="username"
                maxlength="255"
                required
                autofocus
                aria-describedby="@error('login') login-error @else login-help @enderror"
            >
            @error('login')
                <span class="form-error" id="login-error">{{ $message }}</span>
            @else
                <span class="form-help" id="login-help">Gunakan identitas akun yang telah didaftarkan.</span>
            @enderror
        </div>

        <div class="form-group">
            <div class="auth-label-row">
                <label class="form-label" for="password">Kata Sandi</label>
                <a href="{{ route('password.request') }}">Lupa Kata Sandi?</a>
            </div>
            <div class="password-field">
                <input
                    class="form-control @error('password') is-error @enderror"
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Masukkan kata sandi"
                    autocomplete="current-password"
                    required
                    aria-describedby="@error('password') password-error @enderror"
                >
                <button
                    class="password-toggle"
                    type="button"
                    data-password-toggle
                    data-password-target="password"
                    aria-controls="password"
                    aria-label="Tampilkan kata sandi"
                    aria-pressed="false"
                >
                    <span data-password-toggle-label>Tampilkan</span>
                </button>
            </div>
            @error('password')
                <span class="form-error" id="password-error">{{ $message }}</span>
            @enderror
        </div>

        <button class="btn btn-primary btn-lg auth-submit" type="submit" data-login-submit>
            Masuk
        </button>
    </form>

    <p class="auth-security-note">
        Demi keamanan, jangan bagikan kata sandi dan selalu keluar setelah menggunakan perangkat bersama.
    </p>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/auth/login.js') }}" defer></script>
@endpush
