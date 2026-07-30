@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('page-title', 'Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expenses.css') }}">
@endpush

@section('content')
    @php
        $dateFromLabel = ! empty($filters['date_from'])
            ? \Carbon\CarbonImmutable::parse($filters['date_from'])->locale('id')->translatedFormat('d M Y')
            : null;
        $dateToLabel = ! empty($filters['date_to'])
            ? \Carbon\CarbonImmutable::parse($filters['date_to'])->locale('id')->translatedFormat('d M Y')
            : null;
        $periodLabel = match (true) {
            $dateFromLabel && $dateToLabel => $dateFromLabel.' – '.$dateToLabel,
            (bool) $dateFromLabel => 'Mulai '.$dateFromLabel,
            (bool) $dateToLabel => 'Sampai '.$dateToLabel,
            default => 'Semua periode',
        };
        $activeFilterCount = collect($filters)
            ->except('per_page')
            ->filter(fn ($value) => filled($value))
            ->count();
    @endphp

    <div class="expenses-page">
        @include('partials.page-header', [
            'title' => auth()->user()->isOwner() ? 'Pengeluaran' : 'Pengeluaran Cabang',
            'description' => 'Catat biaya operasional dan pantau proses persetujuannya.',
            'eyebrow' => 'Keuangan Operasional',
        ])

        <section class="expenses-mobile-toolbar" aria-label="Periode dan filter pengeluaran">
            <div>
                <span>Periode aktif</span>
                <strong>{{ $periodLabel }}</strong>
                <small>{{ number_format($expenses->total(), 0, ',', '.') }} pengeluaran ditemukan</small>
            </div>
            <button
                class="btn btn-secondary"
                type="button"
                aria-controls="expense-filter-panel"
                aria-expanded="false"
                data-expense-filter-toggle
            >
                Filter{{ $activeFilterCount > 0 ? ' ('.$activeFilterCount.')' : '' }}
            </button>
        </section>

        <div class="module-actions">
            <a class="btn btn-primary" href="{{ route('expenses.create') }}">Tambah Pengeluaran</a>
        </div>

        @include('pages.expenses.sections.expense-summary-cards')
        @include('pages.expenses.sections.expense-filters')
        @include('pages.expenses.sections.expense-table')
        @if (auth()->user()->isOwner())
            @include('pages.expenses.sections.approve-modal')
            @include('pages.expenses.sections.reject-modal')
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expenses.js') }}" defer></script>
@endpush
