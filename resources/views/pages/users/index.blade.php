@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/users.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $viewer->isOwner() ? 'Manajemen Pengguna' : 'Pegawai Cabang',
        'description' => $viewer->isOwner()
            ? 'Kelola role, penempatan cabang, status, dan keamanan akun.'
            : 'Daftar pengguna yang dapat dilihat pada cabang Anda.',
        'eyebrow' => $viewer->isOwner() ? 'Khusus Owner' : 'Read-only',
    ])

    @can('create', \App\Models\User::class)
        <div class="module-actions">
            <a class="btn btn-primary" href="{{ route('users.create') }}">Tambah Pengguna</a>
        </div>
    @endcan

    @include('pages.users.sections.user-summary')
    @include('pages.users.sections.user-filters')
    @include('pages.users.sections.user-table')

    @if ($viewer->isOwner())
        @include('pages.users.sections.status-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/users.js') }}" defer></script>
@endpush
