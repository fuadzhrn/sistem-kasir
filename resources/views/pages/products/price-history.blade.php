@extends('layouts.app')
@section('title', 'Riwayat Harga Produk')
@section('page-title', 'Riwayat Harga')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/products.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/product-price-history.css') }}">
@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Riwayat Harga '.$product->name, 'description' => 'Catatan perubahan harga bersifat permanen dan hanya-baca.', 'eyebrow' => 'Audit Harga'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('products.show', $product) }}">Kembali ke Produk</a></div>
    @include('pages.products.sections.price-history-table')
@endsection
