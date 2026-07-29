@extends('layouts.app')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-adjustments.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Penyesuaian Stok' : 'Penyesuaian Stok Cabang',
        'description' => 'Riwayat koreksi administratif stok yang bersifat final dan dapat diaudit.',
        'eyebrow' => 'Administrasi Stok',
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('stock-adjustments.create') }}">Tambah Penyesuaian Stok</a>
    </div>

    @include('pages.stock-adjustments.sections.adjustment-summary')
    @include('pages.stock-adjustments.sections.adjustment-filters')
    @include('pages.stock-adjustments.sections.adjustment-table')
@endsection
