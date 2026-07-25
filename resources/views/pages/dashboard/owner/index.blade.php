@extends('layouts.app')

@section('title', 'Dashboard Owner')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-owner.css') }}">
@endpush

@section('content')
    <div
        class="owner-dashboard"
        data-owner-dashboard
        data-dashboard-endpoint="{{ route('dashboard.owner.data') }}"
        data-dashboard-page="{{ route('dashboard.owner') }}"
    >
        @include('pages.dashboard.owner.sections.dashboard-header')
        @include('pages.dashboard.owner.sections.dashboard-filters')
        @include('pages.dashboard.owner.sections.dashboard-loading')
        @include('pages.dashboard.owner.sections.dashboard-error')
        @include('pages.dashboard.owner.sections.financial-cards')

        <section class="dashboard-chart-grid" aria-label="Grafik kinerja toko">
            @include('pages.dashboard.owner.sections.sales-trend-chart')
            @include('pages.dashboard.owner.sections.profit-trend-chart')
            @include('pages.dashboard.owner.sections.branch-comparison-chart')
            @include('pages.dashboard.owner.sections.payment-composition-chart')
        </section>

        <section class="dashboard-information-grid" aria-label="Informasi operasional">
            @include('pages.dashboard.owner.sections.top-products')
            @include('pages.dashboard.owner.sections.low-stocks')
        </section>

        @include('pages.dashboard.owner.sections.latest-transactions')
        @include('pages.dashboard.owner.sections.latest-expenses')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}" defer></script>
    <script type="module" src="{{ asset('assets/js/pages/dashboard-owner/index.js') }}"></script>
@endpush
