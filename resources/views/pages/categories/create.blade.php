@extends('layouts.app')

@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Tambah Kategori',
        'description' => 'Tambahkan kategori produk baru.',
        'eyebrow' => 'Master Data Global',
    ])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('categories.index') }}">Kembali</a></div>
    <section class="card form-card">
        <div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            @include('pages.categories.sections.category-form')
        </form>
    </section>
@endsection
