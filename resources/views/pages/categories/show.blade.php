@extends('layouts.app')

@section('title', 'Detail Kategori')
@section('page-title', 'Detail Kategori')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $category->name,
        'description' => 'Informasi kategori global.',
        'eyebrow' => 'Detail Kategori',
    ])
    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('categories.index') }}">Kembali</a>
        @can('update', $category)
            <a class="btn btn-primary" href="{{ route('categories.edit', $category) }}">Edit</a>
        @endcan
    </div>
    <section class="card detail-card">
        <dl class="detail-list">
            <div><dt>Nama</dt><dd>{{ $category->name }}</dd></div>
            <div><dt>Slug</dt><dd>{{ $category->slug }}</dd></div>
            <div><dt>Status</dt><dd><span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
            <div><dt>Jumlah Produk</dt><dd>{{ $category->products_count }}</dd></div>
            <div class="detail-list__full"><dt>Deskripsi</dt><dd>{{ $category->description ?: 'Belum ada deskripsi.' }}</dd></div>
            <div><dt>Diperbarui</dt><dd>{{ $category->updated_at->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd></div>
        </dl>
    </section>
@endsection
