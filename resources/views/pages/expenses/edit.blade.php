@extends('layouts.app')

@section('title', 'Ubah Pengeluaran')
@section('page-title', 'Ubah Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expenses.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Ubah Pengeluaran',
        'description' => 'Hanya pengeluaran yang masih menunggu persetujuan yang dapat diubah.',
        'eyebrow' => 'Pengeluaran',
    ])

    @include('pages.expenses.sections.expense-form')
    @include('pages.expenses.sections.remove-proof-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expenses.js') }}" defer></script>
@endpush
