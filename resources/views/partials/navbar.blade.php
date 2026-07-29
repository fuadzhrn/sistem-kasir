<header class="app-navbar">
    <button
        class="navbar-menu-button"
        type="button"
        data-drawer-toggle
        aria-label="Buka menu navigasi"
        aria-controls="app-navigation"
        aria-expanded="false"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
        </svg>
    </button>

    <div class="navbar-heading">
        <p class="navbar-heading__eyebrow">Sistem Manajemen Toko</p>
        <span class="navbar-heading__mobile-brand">Sistem Kasir</span>
        <h1>@yield('page-title', 'Ruang Kerja')</h1>
    </div>

    <div class="navbar-actions">
        <button class="navbar-icon-button" type="button" aria-label="Notifikasi, belum ada notifikasi baru">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8ZM10 21h4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
            </svg>
            <span class="navbar-icon-button__dot" aria-hidden="true"></span>
        </button>

        <div class="profile-dropdown">
            @auth
                @php
                    $navbarUser = auth()->user()->loadMissing(['role', 'branch']);
                    $navbarInitials = collect(explode(' ', $navbarUser->name))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $name): string => mb_strtoupper(mb_substr($name, 0, 1)))
                        ->implode('');
                    $navbarBranch = $navbarUser->isOwner()
                        ? 'Semua Cabang'
                        : ($navbarUser->branch?->name ?? 'Cabang belum ditetapkan');
                @endphp

                <button
                    class="profile-trigger"
                    type="button"
                    data-dropdown-toggle
                    aria-controls="profile-menu"
                    aria-expanded="false"
                >
                    <span class="profile-trigger__avatar" aria-hidden="true">{{ $navbarInitials ?: 'AK' }}</span>
                    <span class="profile-trigger__identity">
                        <strong>{{ $navbarUser->name }}</strong>
                        <small>{{ $navbarUser->role?->name ?? 'Role belum tersedia' }}</small>
                    </span>
                    <svg class="profile-trigger__chevron" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                        <path d="m6 8 4 4 4-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                    </svg>
                </button>

                <div class="profile-menu" id="profile-menu" data-dropdown-menu hidden>
                    <div class="profile-menu__header">
                        <strong>{{ $navbarUser->name }}</strong>
                        <span>{{ $navbarBranch }}</span>
                    </div>
                    <a href="{{ route('account.index') }}">Akun Saya</a>
                    @if ($navbarUser->isOwner())
                        <a href="{{ route('account.password.edit') }}">Ubah Kata Sandi Saya</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit">Keluar</button>
                    </form>
                </div>
            @else
                <div class="profile-trigger" aria-label="Mode pengembangan lokal">
                    <span class="profile-trigger__avatar" aria-hidden="true">UI</span>
                    <span class="profile-trigger__identity">
                        <strong>Mode Lokal</strong>
                        <small>Design system</small>
                    </span>
                </div>
            @endauth
        </div>
    </div>
</header>
