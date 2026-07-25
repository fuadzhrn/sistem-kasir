@extends('layouts.app')

@section('title', 'Buat Penyesuaian Stok')
@section('page-title', 'Buat Penyesuaian Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-adjustments.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Buat Penyesuaian Stok',
        'description' => 'Gunakan penyesuaian untuk perubahan nonpembelian yang mempunyai alasan audit jelas.',
        'eyebrow' => 'Dokumen Final',
        'breadcrumbs' => [
            ['label' => 'Penyesuaian Stok', 'url' => route('stock-adjustments.index')],
            ['label' => 'Buat'],
        ],
    ])

    @include('pages.stock-adjustments.sections.adjustment-form')
    @include('pages.stock-adjustments.sections.adjustment-confirmation-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stock-adjustments.js') }}" defer></script>
@endpush
