@extends('layouts.app')

@section('title', 'Detail '.$stockReceipt->receipt_number)
@section('page-title', 'Detail Barang Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-receipts.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Detail Barang Masuk',
        'description' => 'Dokumen final penerimaan stok cabang.',
        'eyebrow' => $stockReceipt->receipt_number,
        'breadcrumbs' => [
            ['label' => 'Barang Masuk', 'url' => route('stock-receipts.index')],
            ['label' => $stockReceipt->receipt_number],
        ],
    ])

    @include('pages.stock-receipts.sections.receipt-detail-header')
    @include('pages.stock-receipts.sections.receipt-detail-items')

    @if (auth()->user()->isOwner())
        @include('pages.stock-receipts.sections.hpp-calculation-table')
    @endif

    <div class="alert alert-warning receipt-final-warning" role="note">
        Dokumen penerimaan yang telah disimpan tidak dapat diedit atau dihapus.
    </div>

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('stock-receipts.index') }}">Kembali ke Daftar</a>
    </div>
@endsection
