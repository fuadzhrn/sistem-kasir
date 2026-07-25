@extends('layouts.app')

@section('title', 'Detail Nota '.$sale->invoice_number)
@section('page-title', 'Detail Nota')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/sale-detail.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Detail Nota',
        'description' => 'Snapshot transaksi pada saat penjualan disimpan.',
        'eyebrow' => $sale->invoice_number,
        'breadcrumbs' => [
            ['label' => 'Riwayat Transaksi', 'url' => route('sales.index')],
            ['label' => $sale->invoice_number],
        ],
    ])

    @include('pages.sales.sections.sale-detail-header')
    @include('pages.sales.sections.sale-detail-items')

    <div class="sale-detail-grid">
        @include('pages.sales.sections.sale-payment-summary')
        @if ($showInternal)
            @include('pages.sales.sections.sale-internal-summary')
        @endif
    </div>

    <div class="sale-detail-actions">
        <a class="btn btn-secondary" href="{{ route('sales.index') }}">Kembali ke Riwayat</a>
        <a
            class="btn btn-primary"
            href="{{ route('sales.receipt.show', $sale) }}"
            target="_blank"
            rel="noopener"
        >Cetak Ulang</a>
    </div>
@endsection
