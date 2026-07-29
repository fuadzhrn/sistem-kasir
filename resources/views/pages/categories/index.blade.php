@extends('layouts.app')

@section('title', 'Kategori')
@section('page-title', 'Kategori')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Kategori Produk',
        'description' => 'Kelola pengelompokan produk yang digunakan bersama oleh seluruh cabang.',
        'eyebrow' => 'Master Data Global',
    ])

    @can('create', \App\Models\Category::class)
        <div class="module-actions"><a class="btn btn-primary" href="{{ route('categories.create') }}">Tambah Kategori</a></div>
    @endcan

    @include('pages.categories.sections.category-summary')
    @include('pages.categories.sections.category-filters')
    @include('pages.categories.sections.category-table')
    @include('pages.categories.sections.status-modal')
    @include('pages.categories.sections.delete-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/master-data-mobile.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/categories.js') }}" defer></script>
@endpush
