@extends('layouts.app')

@section('title', 'Barang Masuk')
@section('page-title', 'Barang Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-receipts.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Barang Masuk' : 'Barang Masuk Cabang',
        'description' => 'Catat dan telusuri dokumen penerimaan barang yang telah menambah stok cabang.',
        'eyebrow' => 'Penerimaan Stok',
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('stock-receipts.create') }}">Tambah Barang Masuk</a>
    </div>

    @include('pages.stock-receipts.sections.receipt-summary')
    @include('pages.stock-receipts.sections.receipt-filters')
    @include('pages.stock-receipts.sections.receipt-table')
@endsection
