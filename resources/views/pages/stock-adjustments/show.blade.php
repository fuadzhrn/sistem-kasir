@extends('layouts.app')

@section('title', 'Detail '.$stockAdjustment->adjustment_number)
@section('page-title', 'Detail Penyesuaian Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-adjustments.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Detail Penyesuaian Stok',
        'description' => 'Catatan final perubahan stok administratif.',
        'eyebrow' => $stockAdjustment->adjustment_number,
        'breadcrumbs' => [
            ['label' => 'Penyesuaian Stok', 'url' => route('stock-adjustments.index')],
            ['label' => $stockAdjustment->adjustment_number],
        ],
    ])

    @include('pages.stock-adjustments.sections.adjustment-detail')

    <div class="alert alert-warning adjustment-final-warning">
        Penyesuaian stok telah dicatat permanen dan tidak dapat diedit atau dihapus.
    </div>

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('stock-adjustments.index') }}">Kembali ke Daftar</a>
    </div>
@endsection
