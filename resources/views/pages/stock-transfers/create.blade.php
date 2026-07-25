@extends('layouts.app')

@section('title', 'Buat Permintaan Mutasi')
@section('page-title', 'Buat Permintaan Mutasi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/stock-transfers.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Buat Permintaan Mutasi Stok',
        'description' => 'Permintaan disimpan tanpa mengubah stok dan menunggu persetujuan Owner.',
        'eyebrow' => 'Mutasi Antar-Cabang',
        'breadcrumbs' => [
            ['label' => 'Mutasi Stok', 'url' => route('stock-transfers.index')],
            ['label' => 'Buat'],
        ],
    ])

    @include('pages.stock-transfers.sections.transfer-form')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/stock-transfers.js') }}" defer></script>
@endpush
