<header class="navbar">
    <div class="app-container navbar__inner">
        <a class="navbar__brand" href="{{ url('/') }}">{{ config('app.name') }}</a>

        <nav class="navbar__navigation" aria-label="Navigasi utama">
            <a href="{{ url('/') }}">Beranda</a>
            @if (app()->environment('local'))
                <a href="{{ route('system-check.index') }}">Pemeriksaan Sistem</a>
            @endif
        </nav>
    </div>
</header>
