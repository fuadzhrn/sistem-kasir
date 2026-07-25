@extends('layouts.app')

@section('title', 'Detail Stok')
@section('page-title', 'Detail Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stocks.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-history.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $branchStock->product->name,
        'description' => 'Detail stok dan riwayat perubahan pada '.$branchStock->branch->name.'.',
        'eyebrow' => 'Detail Stok',
        'breadcrumbs' => [
            ['label' => 'Stok', 'url' => route('stocks.index', auth()->user()->isOwner() ? ['branch_id' => $branchStock->branch_id] : [])],
            ['label' => $branchStock->product->code],
        ],
    ])

    <div class="module-actions">
        @if ($canCorrect)
            <a
                class="btn btn-primary"
                href="{{ route('stocks.initial.create', auth()->user()->isOwner()
                    ? ['branch_id' => $branchStock->branch_id, 'product_id' => $branchStock->product_id]
                    : ['product_id' => $branchStock->product_id]) }}"
            >
                Koreksi Stok Awal
            </a>
        @endif
        <a
            class="btn btn-secondary"
            href="{{ route('stocks.history.index', auth()->user()->isOwner()
                ? ['branch_id' => $branchStock->branch_id, 'product_id' => $branchStock->product_id]
                : ['product_id' => $branchStock->product_id]) }}"
        >
            Riwayat Lengkap
        </a>
    </div>

    @include('pages.stocks.sections.stock-detail-card')
    @include('pages.stocks.sections.stock-history-table', ['showBranch' => false])
@endsection
