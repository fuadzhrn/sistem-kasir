@extends('layouts.app')

@section('title', 'Detail Pengeluaran')
@section('page-title', 'Detail Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expenses.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Detail Pengeluaran',
        'description' => 'Informasi pencatatan dan jejak keputusan pengeluaran.',
        'eyebrow' => 'Pengeluaran',
    ])

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('expenses.index') }}">Kembali</a>
        @can('update', $expense)
            <a class="btn btn-secondary" href="{{ route('expenses.edit', $expense) }}">Ubah</a>
        @endcan
        @can('approve', $expense)
            <button
                class="btn btn-success"
                type="button"
                data-expense-approve
                data-action="{{ route('expenses.approve', $expense) }}"
                data-description="{{ \Illuminate\Support\Str::limit($expense->description, 80) }}"
                data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}"
            >Setujui</button>
            <button
                class="btn btn-danger"
                type="button"
                data-expense-reject
                data-action="{{ route('expenses.reject', $expense) }}"
                data-description="{{ \Illuminate\Support\Str::limit($expense->description, 80) }}"
                data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}"
            >Tolak</button>
        @endcan
    </div>

    <div class="expense-detail-grid">
        @include('pages.expenses.sections.expense-detail')
        @include('pages.expenses.sections.expense-proof')
    </div>
    @include('pages.expenses.sections.expense-timeline')
    @if (auth()->user()->isOwner())
        @include('pages.expenses.sections.approve-modal')
        @include('pages.expenses.sections.reject-modal')
    @endif
    @include('pages.expenses.sections.remove-proof-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expenses.js') }}" defer></script>
@endpush
