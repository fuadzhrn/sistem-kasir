@extends('layouts.app')

@section('title', 'Input Stok Awal')
@section('page-title', 'Input Stok Awal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stocks.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Input atau Koreksi Stok Awal',
        'description' => 'Tentukan jumlah akhir stok awal. Setiap perubahan disimpan sebagai riwayat baru.',
        'eyebrow' => 'Stok Per Cabang',
        'breadcrumbs' => [
            ['label' => 'Stok', 'url' => route('stocks.index')],
            ['label' => 'Stok Awal'],
        ],
    ])

    @include('pages.stocks.sections.initial-stock-form')
    @include('pages.stocks.sections.initial-stock-confirmation-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stocks.js') }}" defer></script>
@endpush
