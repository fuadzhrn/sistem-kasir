@extends('layouts.app')

@section('title', 'Pengaturan Toko')
@section('page-title', 'Pengaturan Toko')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/store-settings.css') }}">
@endpush

@section('content')
    <div
        class="store-settings"
        data-store-settings
        data-preview-date="{{ now()->format('Ymd') }}"
    >
        @include('pages.settings.store.sections.settings-header')
        @include('pages.settings.store.sections.settings-navigation')

        <div class="settings-layout">
            <div class="settings-layout__forms">
                @include('pages.settings.store.sections.general-settings-form')
                @include('pages.settings.store.sections.logo-settings')
                @include('pages.settings.store.sections.receipt-settings-form')
                @include('pages.settings.store.sections.business-settings-form')
                @include('pages.settings.store.sections.payment-method-summary')
            </div>

            <aside class="settings-layout__aside">
                @include('pages.settings.store.sections.receipt-preview')
                @include('pages.settings.store.sections.setting-audit-info')
                @include('pages.settings.store.sections.settings-help')
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/store-settings.js') }}" defer></script>
@endpush
