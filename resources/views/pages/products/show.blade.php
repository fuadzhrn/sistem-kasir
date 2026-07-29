@extends('layouts.app')
@section('title', 'Detail Produk')
@section('page-title', 'Detail Produk')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/products.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => $product->name, 'description' => 'Detail produk global dan status penggunaannya.', 'eyebrow' => 'Detail Produk'])
    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('products.index') }}">Kembali</a>
        <a class="btn btn-outline" href="{{ route('products.price-history.index', $product) }}">Riwayat Harga</a>
        <a class="btn btn-primary" href="{{ route('products.edit', $product) }}">Edit</a>
        <a class="btn btn-outline" href="{{ route('products.edit', $product) }}#selling_price">Ubah Harga</a>
        <button class="btn {{ $product->is_active ? 'btn-danger' : 'btn-success' }}" type="button" data-product-status data-action="{{ route('products.status.update', $product) }}" data-name="{{ $product->name }}" data-next-status="{{ $product->is_active ? '0' : '1' }}">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
    </div>
    @include('pages.products.sections.product-detail-card')
    @include('pages.products.sections.status-modal')
    @include('pages.products.sections.remove-image-modal')
@endsection
@push('scripts')<script src="{{ asset('assets/js/pages/products.js') }}" defer></script>@endpush
