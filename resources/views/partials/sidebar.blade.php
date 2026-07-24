@php
    $menuItems = [
        ['label' => 'Design System', 'short' => 'DS', 'route' => 'design-system.index', 'available' => app()->environment('local')],
        ['label' => 'Dashboard', 'short' => 'DB', 'available' => false],
        ['label' => 'Kasir', 'short' => 'KS', 'available' => false],
        ['label' => 'Nota', 'short' => 'NT', 'available' => false],
        ['label' => 'Produk', 'short' => 'PR', 'available' => false],
        ['label' => 'Stok', 'short' => 'ST', 'available' => false],
        ['label' => 'Pengeluaran', 'short' => 'PG', 'available' => false],
        ['label' => 'Laporan', 'short' => 'LP', 'available' => false],
        ['label' => 'Pengguna', 'short' => 'PN', 'available' => false],
        ['label' => 'Pengaturan', 'short' => 'AT', 'available' => false],
    ];
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
                <li>
                    @if ($item['available'])
                        <a
                            class="sidebar-nav__item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                            href="{{ route($item['route']) }}"
                            data-tooltip="{{ $item['label'] }}"
                            @if (request()->routeIs($item['route'])) aria-current="page" @endif
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
            <span class="sidebar-footer__avatar" aria-hidden="true">UI</span>
            <span class="sidebar-footer__text">
                <strong>Mode Pengembangan</strong>
                <small>Fondasi antarmuka</small>
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
