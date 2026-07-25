<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">

    <title>@yield('title', 'Dokumen Cetak') — {{ config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('assets/css/base/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/print/receipt.css') }}">
    @stack('styles')
</head>
<body class="print-document">
    <main class="print-container">
        @yield('content')
    </main>

    <script src="{{ asset('assets/js/pages/receipt.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
