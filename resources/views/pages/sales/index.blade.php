@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page-title', auth()->user()->isOwner() ? 'Nota dan Transaksi' : (auth()->user()->isAdmin() ? 'Nota Cabang' : 'Transaksi Saya'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/sales-history.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Nota dan Transaksi' : (auth()->user()->isAdmin() ? 'Nota Cabang' : 'Transaksi Saya'),
        'description' => 'Telusuri nota tersimpan tanpa mengubah data historis transaksi.',
        'eyebrow' => 'Riwayat Penjualan',
    ])

    @include('pages.sales.sections.sale-summary')
    @include('pages.sales.sections.sale-filters')
    @include('pages.sales.sections.sale-table')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/sales-history.js') }}" defer></script>
@endpush
