@php
    $hasFilters = filled($dashboard['filters']['search'] ?? null)
        || filled($dashboard['filters']['status'] ?? null)
        || filled($dashboard['filters']['date_from'] ?? null)
        || filled($dashboard['filters']['date_to'] ?? null);
@endphp

<div class="cashier-dashboard__empty">
    <span aria-hidden="true">NT</span>
    @if ($hasFilters)
        <h3>Tidak ada transaksi yang sesuai dengan filter.</h3>
        <p>Ubah kata pencarian, tanggal, atau status untuk melihat hasil lain.</p>
        <a class="btn btn-secondary" href="{{ route('dashboard.cashier') }}">Reset Filter</a>
    @else
        <h3>Belum ada transaksi.</h3>
        <p>Tekan tombol Transaksi Baru untuk mulai melayani pelanggan.</p>
        <a class="btn btn-primary" href="{{ route('cashier.index') }}">Mulai Transaksi</a>
    @endif
</div>
