@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('page-title', 'Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expenses.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => auth()->user()->isOwner() ? 'Pengeluaran' : 'Pengeluaran Cabang',
        'description' => 'Catat biaya operasional dan pantau proses persetujuannya.',
        'eyebrow' => 'Keuangan Operasional',
    ])

    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('expenses.create') }}">Catat Pengeluaran</a>
    </div>

    @include('pages.expenses.sections.expense-summary-cards')
    @include('pages.expenses.sections.expense-filters')
    @include('pages.expenses.sections.expense-table')
    @if (auth()->user()->isOwner())
        @include('pages.expenses.sections.approve-modal')
        @include('pages.expenses.sections.reject-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expenses.js') }}" defer></script>
@endpush
