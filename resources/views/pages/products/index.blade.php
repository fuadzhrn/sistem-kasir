@extends('layouts.app')
@section('title', 'Produk')
@section('page-title', 'Produk')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/products.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Master Produk', 'description' => 'Kelola identitas dan harga jual global tanpa mengubah stok cabang.', 'eyebrow' => 'Data Global'])
    <div class="module-actions"><a class="btn btn-primary" href="{{ route('products.create') }}">Tambah Produk</a></div>
    @include('pages.products.sections.product-summary')
    @include('pages.products.sections.product-filters')
    @include('pages.products.sections.product-table', ['isOwner' => auth()->user()->isOwner()])
    @include('pages.products.sections.status-modal')
@endsection
@push('scripts')<script src="{{ asset('assets/js/pages/products.js') }}" defer></script>@endpush
