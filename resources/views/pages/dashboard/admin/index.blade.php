@extends('layouts.app')

@section('title', 'Dashboard Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-owner.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-admin.css') }}">
@endpush

@section('content')
    <div
        class="owner-dashboard admin-dashboard"
        data-admin-dashboard
        data-dashboard-endpoint="{{ route('dashboard.admin.data') }}"
        data-dashboard-page="{{ route('dashboard.admin') }}"
    >
        @include('pages.dashboard.admin.sections.dashboard-header')
        @include('pages.dashboard.admin.sections.dashboard-filters')
        @include('pages.dashboard.admin.sections.dashboard-loading')
        @include('pages.dashboard.admin.sections.dashboard-error')
        @include('pages.dashboard.admin.sections.financial-cards')

        <section class="dashboard-chart-grid" aria-label="Grafik kinerja cabang">
            @include('pages.dashboard.admin.sections.sales-trend-chart')
            @include('pages.dashboard.admin.sections.profit-trend-chart')
            @include('pages.dashboard.admin.sections.branch-performance-chart')
            @include('pages.dashboard.admin.sections.payment-composition-chart')
        </section>

        <section class="dashboard-information-grid" aria-label="Informasi operasional cabang">
            @include('pages.dashboard.admin.sections.top-products')
            @include('pages.dashboard.admin.sections.low-stocks')
        </section>

        @include('pages.dashboard.admin.sections.latest-transactions')
        @include('pages.dashboard.admin.sections.latest-expenses')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}" defer></script>
    <script type="module" src="{{ asset('assets/js/pages/dashboard-admin/index.js') }}"></script>
@endpush
