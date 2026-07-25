@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('page-title', 'Tambah Produk')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/products.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Tambah Produk', 'description' => 'Tambahkan produk global tanpa membuat stok awal.', 'eyebrow' => 'Master Produk'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('products.index') }}">Kembali</a></div>
    <section class="card product-form-card">
        <div class="alert alert-info">Produk dan harga jual digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" data-product-form>
            @csrf
            @include('pages.products.sections.product-form')
        </form>
    </section>
@endsection
@push('scripts')<script src="{{ asset('assets/js/pages/products.js') }}" defer></script>@endpush
