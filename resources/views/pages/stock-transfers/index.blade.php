@extends('layouts.app')

@section('title', 'Mutasi Stok Antar-Cabang')
@section('page-title', 'Mutasi Stok Antar-Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-transfers.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Mutasi Stok Antar-Cabang' : 'Mutasi Stok Cabang',
        'description' => 'Permintaan perpindahan stok antar-cabang dengan persetujuan Owner.',
        'eyebrow' => 'Administrasi Stok',
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('stock-transfers.create') }}">Buat Permintaan Mutasi</a>
    </div>

    @include('pages.stock-transfers.sections.transfer-summary')
    @include('pages.stock-transfers.sections.transfer-filters')
    @include('pages.stock-transfers.sections.transfer-table')
@endsection
