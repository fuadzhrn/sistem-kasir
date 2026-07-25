@extends('layouts.app')

@section('title', 'Tambah Barang Masuk')
@section('page-title', 'Tambah Barang Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-receipts.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Tambah Barang Masuk',
        'description' => 'Satu dokumen dapat memuat sampai 100 jenis produk. Nomor penerimaan dibuat otomatis saat disimpan.',
        'eyebrow' => 'Dokumen Baru',
        'breadcrumbs' => [
            ['label' => 'Barang Masuk', 'url' => route('stock-receipts.index')],
            ['label' => 'Tambah'],
        ],
    ])

    <form
        action="{{ route('stock-receipts.store') }}"
        method="POST"
        class="receipt-form"
        data-stock-receipt-form
        data-branch-name="{{ $branch?->name }}"
    >
        @csrf
        @include('pages.stock-receipts.sections.receipt-header-form')
        @include('pages.stock-receipts.sections.receipt-items-form')

        <div class="receipt-form__footer">
            @include('pages.stock-receipts.sections.receipt-total-card')
            <div class="receipt-form__actions">
                <a class="btn btn-secondary" href="{{ route('stock-receipts.index') }}">Batal</a>
                <button class="btn btn-primary" type="submit" data-receipt-submit>Simpan Barang Masuk</button>
            </div>
        </div>
    </form>

    @include('pages.stock-receipts.sections.receipt-confirmation-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stock-receipts.js') }}" defer></script>
@endpush
