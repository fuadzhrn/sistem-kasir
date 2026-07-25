@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/users.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Tambah Pengguna',
        'description' => 'Buat akun aktif dan tentukan role serta cabang secara aman.',
        'eyebrow' => 'Manajemen Pengguna',
        'breadcrumbs' => [
            ['label' => 'Pengguna', 'url' => route('users.index')],
            ['label' => 'Tambah'],
        ],
    ])

    @include('pages.users.sections.user-form', [
        'action' => route('users.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Pengguna',
        'user' => null,
        'showPassword' => true,
    ])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/users.js') }}" defer></script>
@endpush
