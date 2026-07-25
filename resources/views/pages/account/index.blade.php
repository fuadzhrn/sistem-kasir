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
                <div><dt>Role</dt><dd>{{ $user->role?->name ?? 'Belum tersedia' }}</dd></div>
                <div><dt>Cabang</dt><dd>{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? 'Belum ditetapkan') }}</dd></div>
                <div><dt>Status akun</dt><dd><span class="badge badge-success">Aktif</span></dd></div>
                <div><dt>Login terakhir</dt><dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum tercatat' }}</dd></div>
            </dl>
        </article>

        <aside class="card account-security">
            <span class="account-security__label">Keamanan</span>
            @if ($user->isOwner())
                <h3>Kelola kata sandi pengguna</h3>
                <p>Owner dapat menetapkan kata sandi baru untuk seluruh akun tanpa melihat password lama.</p>
            @else
                <h3>Kata sandi dikelola Owner</h3>
                <p>Hubungi Owner apabila kata sandi akun perlu diganti atau direset.</p>
            @endif

            <div class="account-security__actions">
                @if ($user->isOwner())
                    <a class="btn btn-primary" href="{{ route('account.password.edit') }}">Kelola Kata Sandi</a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-secondary" type="submit">Logout</button>
                </form>
            </div>
        </aside>
    </section>
@endsection
