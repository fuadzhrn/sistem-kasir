@extends('layouts.app')
@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/products.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Edit Produk', 'description' => 'Perubahan harga akan dicatat permanen.', 'eyebrow' => 'Master Produk'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('products.show', $product) }}">Batal</a></div>
    <section class="card product-form-card">
        <div class="alert alert-warning">Produk dan harga jual digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" data-product-form data-product-name="{{ $product->name }}" data-old-selling-price="{{ \App\Support\Format\Rupiah::input($product->selling_price) }}" @if($isOwner) data-old-purchase-price="{{ \App\Support\Format\Rupiah::input($product->purchase_price) }}" @endif>
            @csrf
            @method('PUT')
            @include('pages.products.sections.product-form', ['product' => $product])
        </form>
    </section>
    @include('pages.products.sections.price-confirmation-modal')
@endsection
@push('scripts')<script src="{{ asset('assets/js/pages/products.js') }}" defer></script>@endpush
