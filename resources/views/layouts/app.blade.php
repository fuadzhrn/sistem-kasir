<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="{{ asset('assets/css/base/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/typography.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/global.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layouts/app-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layouts/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layouts/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/cards.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/alerts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/empty-state.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/loading.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/pagination.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app-shell" data-app-shell>
        @include('partials.sidebar')

        <div class="app-main">
            @include('partials.navbar')

            <main class="app-content" id="main-content">
                <div class="app-content__inner">
                    @include('partials.alert')
                    @yield('content')
                </div>
            </main>

            @include('partials.footer')
        </div>
    </div>

    @include('partials.modal-confirm')
    <div class="toast-container" data-toast-container aria-live="polite" aria-atomic="true"></div>

    <script src="{{ asset('assets/js/core/helpers.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/quantity.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/csrf.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/app.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/sidebar.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/dropdown.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/modal.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/confirm.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/toast.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
