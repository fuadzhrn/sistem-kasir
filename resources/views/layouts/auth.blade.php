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
    <link rel="stylesheet" href="{{ asset('assets/css/components/forms.css') }}">
    @stack('styles')
</head>
<body>
    <main class="auth-shell">
        @yield('content')
    </main>

    <script src="{{ asset('assets/js/core/helpers.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/csrf.js') }}" defer></script>
    <script src="{{ asset('assets/js/core/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
