@php
    $sidebarUser = auth()->user();
    $sidebarUser?->loadMissing(['role', 'branch']);

    $guestMenu = [
        ['label' => 'Design System', 'short' => 'DS', 'route' => 'design-system.index', 'available' => app()->environment('local')],
    ];
    $ownerMenu = [
        ['label' => 'Dashboard', 'short' => 'DB'],
        ['label' => 'Kasir', 'short' => 'KS'],
        ['label' => 'Nota', 'short' => 'NT'],
        ['label' => 'Produk', 'short' => 'PR'],
        ['label' => 'Stok', 'short' => 'ST'],
        ['label' => 'Pengeluaran', 'short' => 'PG'],
        ['label' => 'Laporan', 'short' => 'LP'],
        ['label' => 'Cabang', 'short' => 'CB', 'route' => 'branches.index', 'active' => 'branches.*', 'available' => \Illuminate\Support\Facades\Gate::allows('manage-branches')],
        ['label' => 'Pengguna', 'short' => 'PN', 'route' => 'users.index', 'active' => 'users.*', 'available' => \Illuminate\Support\Facades\Gate::allows('manage-users')],
        ['label' => 'Aktivitas', 'short' => 'AK'],
        ['label' => 'Pengaturan', 'short' => 'AT'],
        ['label' => 'Akun Saya', 'short' => 'AS', 'route' => 'account.index', 'available' => true],
    ];
    $adminMenu = [
        ['label' => 'Dashboard Cabang', 'short' => 'DB'],
        ['label' => 'Kasir', 'short' => 'KS'],
        ['label' => 'Nota Cabang', 'short' => 'NT'],
        ['label' => 'Produk', 'short' => 'PR'],
        ['label' => 'Stok Cabang', 'short' => 'ST'],
        ['label' => 'Pengeluaran Cabang', 'short' => 'PG'],
        ['label' => 'Laporan Cabang', 'short' => 'LP'],
        ['label' => 'Cabang Saya', 'short' => 'CB', 'route' => 'my-branch.show', 'active' => 'my-branch.*', 'available' => $sidebarUser?->isAdmin()],
        ['label' => 'Pegawai Cabang', 'short' => 'PC', 'route' => 'users.index', 'active' => 'users.*', 'available' => $sidebarUser?->isAdmin()],
        ['label' => 'Akun Saya', 'short' => 'AS', 'route' => 'account.index', 'available' => true],
    ];
    $cashierMenu = [
        ['label' => 'Transaksi Baru', 'short' => 'TB'],
        ['label' => 'Transaksi Saya', 'short' => 'TS'],
        ['label' => 'Cetak Ulang Nota', 'short' => 'CN'],
        ['label' => 'Akun Saya', 'short' => 'AS', 'route' => 'account.index', 'available' => true],
    ];

    $menuItems = $guestMenu;

    if ($sidebarUser?->isOwner()) {
        $menuItems = $ownerMenu;
    } elseif ($sidebarUser?->isAdmin()) {
        $menuItems = $adminMenu;
    } elseif ($sidebarUser?->isCashier()) {
        $menuItems = $cashierMenu;
    }

    $sidebarRole = $sidebarUser?->role?->name ?? 'Mode Pengembangan';
    $sidebarBranch = $sidebarUser?->isOwner()
        ? 'Semua Cabang'
        : ($sidebarUser?->branch?->name ?? ($sidebarUser ? 'Cabang belum ditetapkan' : 'Fondasi antarmuka'));
@endphp

<aside class="app-sidebar" data-sidebar aria-label="Navigasi aplikasi">
    <div class="sidebar-brand">
        <span class="sidebar-brand__mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M19.5 4.5c-6.3.3-10.1 2.4-11.7 6.2-1 2.4-.5 4.8.2 6.3 1.3-3.1 3.6-5.7 7.1-7.8-3 2.7-4.8 5.7-5.5 9.1 1.8.9 4.3.8 6.3-.6 3.8-2.6 4.1-7.7 3.6-13.2Z"/>
            </svg>
        </span>
        <span class="sidebar-brand__text">
            <strong>Sistem Manajemen</strong>
            <small>Toko Pertanian</small>
        </span>
    </div>

    <nav class="sidebar-nav" aria-label="Menu utama">
        <p class="sidebar-nav__label">Ruang kerja</p>
        <ul class="sidebar-nav__list">
            @foreach ($menuItems as $item)
                @php
                    $available = $item['available'] ?? false;
                    $routeName = $item['route'] ?? null;
                    $activePattern = $item['active'] ?? $routeName;
                @endphp
                <li>
                    @if ($available && $routeName)
                        <a
                            class="sidebar-nav__item {{ request()->routeIs($activePattern) ? 'is-active' : '' }}"
                            href="{{ route($routeName) }}"
                            data-tooltip="{{ $item['label'] }}"
                            @if (request()->routeIs($activePattern)) aria-current="page" @endif
                        >
                            <span class="sidebar-nav__icon" aria-hidden="true">{{ $item['short'] }}</span>
                            <span class="sidebar-nav__text">{{ $item['label'] }}</span>
                        </a>
                    @else
                        <button
                            class="sidebar-nav__item is-disabled"
                            type="button"
                            disabled
                            data-tooltip="{{ $item['label'] }} — Segera"
                        >
                            <span class="sidebar-nav__icon" aria-hidden="true">{{ $item['short'] }}</span>
                            <span class="sidebar-nav__text">{{ $item['label'] }}</span>
                            <span class="sidebar-nav__badge">Segera</span>
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer__info">
            <span class="sidebar-footer__avatar" aria-hidden="true">{{ $sidebarUser ? 'AK' : 'UI' }}</span>
            <span class="sidebar-footer__text">
                <strong>{{ $sidebarRole }}</strong>
                <small>{{ $sidebarBranch }}</small>
            </span>
        </div>

        <button
            class="sidebar-toggle"
            type="button"
            data-sidebar-toggle
            aria-label="Perkecil sidebar"
            aria-expanded="true"
            data-tooltip="Perkecil sidebar"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="m14.5 6-6 6 6 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
        </button>
    </div>
</aside>
