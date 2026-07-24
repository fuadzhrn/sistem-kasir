<header class="app-navbar">
    <div class="navbar-heading">
        <p class="navbar-heading__eyebrow">Sistem Manajemen Toko</p>
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
            <button
                class="profile-trigger"
                type="button"
                data-dropdown-toggle
                aria-controls="profile-menu"
                aria-expanded="false"
            >
                <span class="profile-trigger__avatar" aria-hidden="true">DU</span>
                <span class="profile-trigger__identity">
                    <strong>Demo UI</strong>
                    <small>Mode lokal</small>
                </span>
                <svg class="profile-trigger__chevron" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                    <path d="m6 8 4 4 4-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                </svg>
            </button>

            <div class="profile-menu" id="profile-menu" data-dropdown-menu hidden>
                <div class="profile-menu__header">
                    <strong>Demo UI</strong>
                    <span>Placeholder tanpa autentikasi</span>
                </div>
                <button type="button" disabled>Profil — segera</button>
                <button type="button" disabled>Keluar — segera</button>
            </div>
        </div>
    </div>
</header>
