@extends('layouts.app')

@section('title', 'Detail Pengguna')
@section('page-title', 'Detail Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/users.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $user->name,
        'description' => 'Informasi akun tanpa password, token, atau credential sensitif.',
        'eyebrow' => $user->role?->name ?? 'Role tidak tersedia',
        'breadcrumbs' => [
            ['label' => 'Pengguna', 'url' => route('users.index')],
            ['label' => $user->name],
        ],
    ])

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('users.index') }}">Kembali</a>
        @can('update', $user)
            <a class="btn btn-primary" href="{{ route('users.edit', $user) }}">Edit Pengguna</a>
        @endcan
        @can('resetPassword', $user)
            <a class="btn btn-outline" href="{{ route('users.password.edit', $user) }}">Reset Kata Sandi</a>
        @endcan
    </div>

    <section class="card detail-card">
        <div class="user-identity">
            <span class="user-identity__avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div>
                <h3>{{ $user->name }}</h3>
                <p>{{ '@'.$user->username }}</p>
            </div>
        </div>
        <dl class="detail-list">
            <div><dt>Username</dt><dd>{{ $user->username }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email ?: 'Belum tersedia' }}</dd></div>
            <div><dt>Role</dt><dd>{{ $user->role?->name ?? 'Tidak tersedia' }}</dd></div>
            <div><dt>Cabang</dt><dd>{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? 'Belum ditetapkan') }}</dd></div>
            <div><dt>Status</dt><dd><span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
            <div><dt>Login terakhir</dt><dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum tercatat' }}</dd></div>
            <div><dt>Dibuat</dt><dd>{{ $user->created_at->format('d M Y, H:i') }}</dd></div>
            <div><dt>Diperbarui</dt><dd>{{ $user->updated_at->format('d M Y, H:i') }}</dd></div>
        </dl>
    </section>
@endsection
