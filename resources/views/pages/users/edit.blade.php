@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/users.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Edit '.$user->name,
        'description' => 'Perbarui identitas, role, dan penempatan cabang tanpa mengubah password atau status.',
        'eyebrow' => 'Manajemen Pengguna',
        'breadcrumbs' => [
            ['label' => 'Pengguna', 'url' => route('users.index')],
            ['label' => $user->name, 'url' => route('users.show', $user)],
            ['label' => 'Edit'],
        ],
    ])

    @include('pages.users.sections.user-form', [
        'action' => route('users.update', $user),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
        'user' => $user,
        'showPassword' => false,
    ])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/users.js') }}" defer></script>
@endpush
