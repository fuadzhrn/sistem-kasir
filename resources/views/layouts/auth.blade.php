<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Autentikasi') — {{ config('app.name') }}</title>

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
    <link rel="stylesheet" href="{{ asset('assets/css/layouts/auth-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/forms.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/alerts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/toast.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
</head>
<body class="@yield('body-class')">
    <main class="auth-shell">
        @hasSection('auth-page')
            @yield('auth-page')
        @else
            <div class="auth-container">
                <div class="auth-brand" aria-label="Sistem Manajemen Toko">
                    <span class="auth-brand__mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M19.5 4.5c-6.3.3-10.1 2.4-11.7 6.2-1 2.4-.5 4.8.2 6.3 1.3-3.1 3.6-5.7 7.1-7.8-3 2.7-4.8 5.7-5.5 9.1 1.8.9 4.3.8 6.3-.6 3.8-2.6 4.1-7.7 3.6-13.2Z"/>
                        </svg>
                    </span>
                    <strong>Sistem Manajemen Toko</strong>
                </div>

                <section class="auth-card">
                    @include('partials.alert')
                    @yield('content')
                </section>

                <p class="auth-caption">Akses aman untuk pengguna terdaftar</p>
            </div>
        @endif
    </main>

    <div class="toast-container" data-toast-container aria-live="polite" aria-atomic="true"></div>

    <script src="{{ asset('assets/js/core/helpers.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/csrf.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/app.js') }}" defer></script>
    <script src="{{ asset('assets/js/components/toast.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
