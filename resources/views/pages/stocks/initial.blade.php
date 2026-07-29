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

    <div class="alert alert-info opening-stock-guidance" role="status">
        <span class="alert__icon" aria-hidden="true">i</span>
        <div class="alert__content">
            <h2 class="alert__title">Tentang stok awal</h2>
            <p class="alert__message">
                Stok awal digunakan ketika produk pertama kali dicatat pada suatu cabang dan tidak perlu dimasukkan kembali setiap hari.
            </p>
        </div>
    </div>

    @include('pages.stocks.sections.initial-stock-form')
    @include('pages.stocks.sections.initial-stock-confirmation-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stocks.js') }}" defer></script>
@endpush
