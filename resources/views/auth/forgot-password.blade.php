@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/forgot-password.css') }}">
@endpush

@section('content')
    <header class="auth-heading">
        <p class="auth-heading__eyebrow">Pemulihan akun</p>
        <h1>Lupa kata sandi?</h1>
        <p>Masukkan email akun Anda. Kami akan mengirim petunjuk pengaturan ulang jika akun memenuhi syarat.</p>
    </header>

    <form class="auth-form" action="{{ route('password.email') }}" method="POST">
        @csrf

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control @error('email') is-error @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                placeholder="Masukkan email terdaftar"
                autocomplete="email"
                maxlength="255"
                required
                autofocus
                aria-describedby="@error('email') email-error @else email-help @enderror"
            >
            @error('email')
                <span class="form-error" id="email-error">{{ $message }}</span>
            @else
                <span class="form-help" id="email-help">Respons yang sama diberikan untuk setiap alamat email.</span>
            @enderror
        </div>

        <button class="btn btn-primary btn-lg auth-submit" type="submit">
            Kirim Tautan Reset
        </button>
    </form>

    <div class="auth-back-link">
        <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
    </div>
@endsection
