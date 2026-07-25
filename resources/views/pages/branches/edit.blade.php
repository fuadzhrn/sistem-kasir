@extends('layouts.app')

@section('title', 'Edit Cabang')
@section('page-title', 'Edit Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/branches.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Edit '.$branch->name,
        'description' => 'Perbarui identitas cabang tanpa mengubah statusnya.',
        'eyebrow' => 'Manajemen Cabang',
        'breadcrumbs' => [
            ['label' => 'Cabang', 'url' => route('branches.index')],
            ['label' => $branch->name, 'url' => route('branches.show', $branch)],
            ['label' => 'Edit'],
        ],
    ])

    @include('pages.branches.sections.branch-form', [
        'action' => route('branches.update', $branch),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
        'branch' => $branch,
        'canChangeCode' => $canChangeCode,
    ])
@endsection
