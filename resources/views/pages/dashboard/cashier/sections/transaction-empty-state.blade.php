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
        <h3>Belum ada transaksi yang Anda buat.</h3>
        <p>Mulai transaksi pertama dari ruang kasir.</p>
        <a class="btn btn-primary" href="{{ route('cashier.index') }}">Buat Transaksi Baru</a>
    @endif
</div>
