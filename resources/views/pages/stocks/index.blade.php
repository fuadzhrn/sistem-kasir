@extends('layouts.app')

@section('title', 'Stok')
@section('page-title', 'Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stocks.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $selectedBranch ? 'Stok '.$selectedBranch->name : 'Ringkasan Stok Semua Cabang',
        'description' => $selectedBranch
            ? 'Pantau jumlah dan status stok produk pada cabang yang dipilih.'
            : 'Bandingkan jumlah SKU berdasarkan status tanpa menjumlahkan satuan yang berbeda.',
        'eyebrow' => 'Stok Per Cabang',
    ])

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('stocks.history.index') }}">Riwayat Stok</a>
        <a
            class="btn btn-primary"
            href="{{ route('stocks.initial.create', $selectedBranch ? ['branch_id' => $selectedBranch->id] : []) }}"
        >
            Input Stok Awal
        </a>
    </div>

    @if ($branchSummaries !== null)
        @include('pages.stocks.sections.stock-branch-summary')
    @else
        @include('pages.stocks.sections.stock-summary-cards')
        @include('pages.stocks.sections.stock-filters')
        @include('pages.stocks.sections.stock-table')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stocks.js') }}" defer></script>
@endpush
