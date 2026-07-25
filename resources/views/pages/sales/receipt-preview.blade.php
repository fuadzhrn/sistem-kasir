@extends('layouts.print')

@section('title', 'Preview Nota '.$sale->invoice_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/receipt-preview.css') }}">
@endpush

@section('content')
    <div class="receipt-preview">
        @if ($sale->status === \App\Models\Sale::STATUS_VOIDED)
            <div class="receipt-watermark receipt-watermark--danger">TRANSAKSI DIBATALKAN</div>
        @elseif ($sale->status === \App\Models\Sale::STATUS_VOID_REQUESTED)
            <div class="receipt-watermark receipt-watermark--warning">MENUNGGU PEMBATALAN</div>
        @endif

        @include('pages.sales.sections.receipt-header')
        @include('pages.sales.sections.receipt-items')
        @include('pages.sales.sections.receipt-payment-summary')
        @include('pages.sales.sections.receipt-footer')
    </div>
@endsection
