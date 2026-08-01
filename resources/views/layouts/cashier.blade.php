<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir') — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/base/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/typography.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/global.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/cards.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/alerts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/empty-state.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/loading.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/cashier.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/cashier-responsive.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/cashier-bottom-navigation.css') }}">
</head>
@php
    $layoutUser = auth()->user();
    $showCashierBottomNavigation = $layoutUser?->isCashier() ?? false;
@endphp
<body @class([
    'cashier-body',
    'has-cashier-bottom-navigation' => $showCashierBottomNavigation,
])>
    @include('pages.cashier.sections.cashier-header')

    <main class="cashier-main" id="cashier-main">
        @yield('content')
    </main>

    @if ($showCashierBottomNavigation)
        @include('partials.cashier-bottom-navigation')
    @endif

    <div class="toast-container" data-toast-container aria-live="polite" aria-atomic="true"></div>
    <script src="{{ asset('assets/js/core/quantity.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/modal.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/toast.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
