@extends('layouts.app')

@section('title', 'Akun Saya')
@section('page-title', 'Akun Saya')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/account.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Akun Saya',
        'description' => 'Informasi dasar akun yang sedang digunakan.',
        'eyebrow' => 'Akun aman',
    ])

    <section class="account-grid" aria-label="Informasi akun">
        <article class="card account-card">
            <div class="account-card__identity">
                <span class="account-card__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div>
                    <h3>{{ $user->name }}</h3>
                    <p>{{ '@'.$user->username }}</p>
                </div>
            </div>

            <dl class="account-details">
                <div><dt>Username</dt><dd>{{ $user->username }}</dd></div>
                <div><dt>Email</dt><dd>{{ $user->email ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Status akun</dt><dd><span class="badge badge-success">Aktif</span></dd></div>
                <div><dt>Login terakhir</dt><dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum tercatat' }}</dd></div>
            </dl>
        </article>

        <aside class="card account-security">
            <span class="account-security__label">Keamanan</span>
            <h3>Lindungi akses akun</h3>
            <p>Gunakan kata sandi yang unik dan selalu keluar setelah memakai perangkat bersama.</p>

            <div class="account-security__actions">
                <a class="btn btn-primary" href="{{ route('account.password.edit') }}">Ubah Kata Sandi</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-secondary" type="submit">Logout</button>
                </form>
            </div>
        </aside>
    </section>
@endsection
