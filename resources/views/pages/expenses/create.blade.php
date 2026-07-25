@extends('layouts.app')

@section('title', 'Catat Pengeluaran')
@section('page-title', 'Catat Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expenses.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Catat Pengeluaran',
        'description' => 'Pengeluaran baru akan berstatus menunggu persetujuan.',
        'eyebrow' => 'Pengeluaran',
    ])

    @include('pages.expenses.sections.expense-form', ['expense' => null])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expenses.js') }}" defer></script>
@endpush
