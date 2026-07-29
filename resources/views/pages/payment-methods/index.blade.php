@extends('layouts.app')
@section('title', 'Metode Pembayaran')
@section('page-title', 'Metode Pembayaran')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/payment-methods.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Metode Pembayaran', 'description' => 'Kelola pilihan pembayaran global dan urutan tampilnya.', 'eyebrow' => 'Master Data Global'])
    @can('create', \App\Models\PaymentMethod::class)<div class="module-actions"><a class="btn btn-primary" href="{{ route('payment-methods.create') }}">Tambah Metode Pembayaran</a></div>@endcan
    @include('pages.payment-methods.sections.payment-method-summary')
    @include('pages.payment-methods.sections.payment-method-filters')
    @include('pages.payment-methods.sections.payment-method-table')
    @include('pages.payment-methods.sections.status-modal')
    @include('pages.payment-methods.sections.delete-modal')
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/pages/master-data-mobile.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/payment-methods.js') }}" defer></script>
@endpush
