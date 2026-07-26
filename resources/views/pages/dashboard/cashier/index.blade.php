@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-cashier.css') }}">
@endpush

@section('content')
    <div class="cashier-dashboard" data-cashier-dashboard>
        @include('pages.dashboard.cashier.sections.cashier-welcome')
        @include('pages.dashboard.cashier.sections.new-transaction-action')
        @include('pages.dashboard.cashier.sections.today-summary')
        @include('pages.dashboard.cashier.sections.transaction-filters')
        @include('pages.dashboard.cashier.sections.transaction-history')
        @include('pages.dashboard.cashier.sections.cashier-help')
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/pages/dashboard-cashier.js') }}"></script>
@endpush
