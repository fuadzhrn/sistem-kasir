@extends('layouts.app')

@section('title', 'Detail Nota '.$sale->invoice_number)
@section('page-title', 'Detail Nota')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/sale-detail.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/sale-void.css') }}">
@endpush

@section('content')
    <div class="sale-detail">
        @include('partials.page-header', [
            'title' => 'Detail Transaksi',
            'description' => 'Data historis sesuai kondisi saat transaksi disimpan.',
            'eyebrow' => $sale->invoice_number,
            'breadcrumbs' => [
                ['label' => 'Riwayat Transaksi', 'url' => route('sales.index')],
                ['label' => $sale->invoice_number],
            ],
        ])

        @include('pages.sales.sections.sale-detail-header')
        @include('pages.sales.sections.void-information')
        @include('pages.sales.sections.sale-detail-items')

        <div class="sale-detail-grid">
            @include('pages.sales.sections.sale-payment-summary')
            @if ($showInternal)
                @include('pages.sales.sections.sale-internal-summary')
            @endif
        </div>

        <div class="sale-detail-actions">
            <a class="btn btn-secondary" href="{{ route('sales.index') }}">Kembali ke Riwayat</a>
            @include('pages.sales.sections.void-action')
            <form method="POST" action="{{ route('sales.receipt.reprint', $sale) }}" target="receipt-print">
                @csrf
                <button class="btn btn-primary" type="submit">Cetak Ulang Struk</button>
            </form>
        </div>
        @include('pages.sales.sections.void-confirmation-modal')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/sale-void.js') }}" defer></script>
@endpush
