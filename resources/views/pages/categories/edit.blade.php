@extends('layouts.app')

@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Edit Kategori',
        'description' => 'Perbarui identitas kategori tanpa mengubah statusnya.',
        'eyebrow' => 'Master Data Global',
    ])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('categories.show', $category) }}">Batal</a></div>
    <section class="card form-card">
        <div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('pages.categories.sections.category-form', ['category' => $category])
        </form>
    </section>
@endsection
