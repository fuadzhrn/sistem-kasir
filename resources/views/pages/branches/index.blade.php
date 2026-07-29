@extends('layouts.app')

@section('title', 'Cabang')
@section('page-title', 'Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/branches.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Manajemen Cabang',
        'description' => 'Kelola identitas dan status cabang tanpa menghapus histori.',
        'eyebrow' => 'Khusus Owner',
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('branches.create') }}">Tambah Cabang</a>
    </div>

    @include('pages.branches.sections.branch-summary')
    @include('pages.branches.sections.branch-filters')
    @include('pages.branches.sections.branch-table')
    @include('pages.branches.sections.status-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/master-data-mobile.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/branches.js') }}" defer></script>
@endpush
