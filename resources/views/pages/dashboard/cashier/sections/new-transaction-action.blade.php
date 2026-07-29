<section class="cashier-dashboard__primary-action" aria-label="Aksi utama">
    <div class="cashier-dashboard__primary-copy">
        <span class="cashier-dashboard__primary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M3 5h2l1.6 8.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L19.5 8H6M9 19a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm9 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
            </svg>
        </span>
        <div>
            <h2>Siap melayani pelanggan?</h2>
            <p>Mulai transaksi penjualan dan buat nota baru.</p>
        </div>
    </div>
    <a class="btn btn-primary cashier-dashboard__new-sale" href="{{ route('cashier.index') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
        </svg>
        Transaksi Baru
    </a>
</section>
