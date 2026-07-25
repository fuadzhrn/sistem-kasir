@extends('layouts.app')

@section('title', 'Riwayat Stok')
@section('page-title', 'Riwayat Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stocks.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-history.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Riwayat Stok Semua Cabang' : 'Riwayat Stok Cabang',
        'description' => 'Catatan read-only stok sebelum, perubahan, stok setelah, alasan, dan pelakunya.',
        'eyebrow' => 'Audit Stok',
        'breadcrumbs' => [
            ['label' => 'Stok', 'url' => route('stocks.index')],
            ['label' => 'Riwayat'],
        ],
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('stocks.initial.create') }}">Input Stok Awal</a>
    </div>

    @include('pages.stocks.sections.stock-history-filters')
    @include('pages.stocks.sections.stock-history-table', ['showBranch' => true])
@endsection
