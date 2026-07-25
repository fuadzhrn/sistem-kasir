@extends('layouts.app')

@section('title', 'Tambah Cabang')
@section('page-title', 'Tambah Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/branches.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Tambah Cabang',
        'description' => 'Masukkan identitas cabang baru. Status awal cabang adalah aktif.',
        'eyebrow' => 'Manajemen Cabang',
        'breadcrumbs' => [
            ['label' => 'Cabang', 'url' => route('branches.index')],
            ['label' => 'Tambah'],
        ],
    ])

    @include('pages.branches.sections.branch-form', [
        'action' => route('branches.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Cabang',
        'branch' => null,
        'canChangeCode' => true,
    ])
@endsection
