@extends('layouts.app')

@section('title', 'Detail '.$stockTransfer->transfer_number)
@section('page-title', 'Detail Mutasi Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-transfers.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Detail Mutasi Stok',
        'description' => 'Status permintaan dan jejak pemrosesan mutasi antar-cabang.',
        'eyebrow' => $stockTransfer->transfer_number,
        'breadcrumbs' => [
            ['label' => 'Mutasi Stok', 'url' => route('stock-transfers.index')],
            ['label' => $stockTransfer->transfer_number],
        ],
    ])

    @include('pages.stock-transfers.sections.transfer-detail')

    @if ($stockTransfer->isPending())
        <div class="alert alert-warning transfer-pending-warning">
            Stok belum berubah. Perpindahan baru dilakukan secara atomik setelah Owner menyelesaikan permintaan ini.
        </div>
    @endif

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('stock-transfers.index') }}">Kembali ke Daftar</a>
        @can('cancel', $stockTransfer)
            <button class="btn btn-outline" type="button" data-modal-open="transfer-cancel-modal">Batalkan</button>
        @endcan
        @can('reject', $stockTransfer)
            <button class="btn btn-danger" type="button" data-modal-open="transfer-reject-modal">Tolak</button>
        @endcan
        @can('complete', $stockTransfer)
            <button class="btn btn-primary" type="button" data-modal-open="transfer-complete-modal">Selesaikan Mutasi</button>
        @endcan
    </div>

    @include('pages.stock-transfers.sections.complete-modal')
    @include('pages.stock-transfers.sections.reject-modal')
    @include('pages.stock-transfers.sections.cancel-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stock-transfers.js') }}" defer></script>
@endpush
