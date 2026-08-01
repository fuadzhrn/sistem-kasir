<nav class="cashier-bottom-navigation" aria-label="Navigasi cepat Kasir">
    <a
        class="cashier-bottom-navigation__item {{ request()->routeIs('dashboard.cashier') ? 'is-active' : '' }}"
        href="{{ route('dashboard.cashier') }}"
        @if (request()->routeIs('dashboard.cashier')) aria-current="page" @endif
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4 10.5 12 4l8 6.5V20h-5v-6H9v6H4v-9.5Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        </svg>
        <span>Beranda</span>
    </a>
    <a
        class="cashier-bottom-navigation__item {{ request()->routeIs('cashier.*') ? 'is-active' : '' }}"
        href="{{ route('cashier.index') }}"
        @if (request()->routeIs('cashier.*')) aria-current="page" @endif
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4 6h16v14H4V6Zm3-3v6m10-6v6M8 13h8m-8 4h5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        </svg>
        <span>Transaksi</span>
    </a>
    <a
        class="cashier-bottom-navigation__item {{ request()->routeIs('sales.*') ? 'is-active' : '' }}"
        href="{{ route('sales.index') }}"
        @if (request()->routeIs('sales.*')) aria-current="page" @endif
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M7 4h10a2 2 0 0 1 2 2v14l-3-2-2 2-2-2-2 2-2-2-3 2V6a2 2 0 0 1 2-2Zm2 5h6m-6 4h6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        </svg>
        <span>Riwayat</span>
    </a>
    <a
        class="cashier-bottom-navigation__item {{ request()->routeIs('account.*') ? 'is-active' : '' }}"
        href="{{ route('account.index') }}"
        @if (request()->routeIs('account.*')) aria-current="page" @endif
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
        </svg>
        <span>Akun</span>
    </a>
</nav>
