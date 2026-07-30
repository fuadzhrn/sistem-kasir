@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/sales-history.css') }}">
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

    <div class="sales-history-page">
        @include('partials.page-header', [
            'title' => 'Riwayat Transaksi',
            'description' => 'Telusuri nota tersimpan tanpa mengubah data historis transaksi.',
            'eyebrow' => auth()->user()->isOwner() ? 'Seluruh Cabang' : (auth()->user()->isAdmin() ? 'Cabang Anda' : 'Transaksi Saya'),
        ])

        <section class="sales-mobile-toolbar" aria-label="Periode dan filter transaksi">
            <div>
                <span>Periode aktif</span>
                <strong>{{ $periodLabel }}</strong>
                <small>{{ number_format($sales->total(), 0, ',', '.') }} transaksi ditemukan</small>
            </div>
            <button
                class="btn btn-secondary"
                type="button"
                aria-controls="sales-filter-panel"
                aria-expanded="false"
                data-sales-filter-toggle
            >
                Filter{{ $activeFilterCount > 0 ? ' ('.$activeFilterCount.')' : '' }}
            </button>
        </section>

        @include('pages.sales.sections.sale-summary')
        @include('pages.sales.sections.sale-filters')
        @include('pages.sales.sections.sale-table')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/sales-history.js') }}" defer></script>
@endpush
