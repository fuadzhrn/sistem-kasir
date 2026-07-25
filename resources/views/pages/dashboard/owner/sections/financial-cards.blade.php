@php
    $financialCards = [
        ['key' => 'gross_sales', 'title' => 'Omzet', 'icon' => 'OM', 'description' => 'Penjualan sebelum diskon'],
        ['key' => 'net_sales', 'title' => 'Penjualan Bersih', 'icon' => 'PB', 'description' => 'Penjualan setelah diskon'],
        ['key' => 'cost_of_goods_sold', 'title' => 'HPP', 'icon' => 'HP', 'description' => 'Total harga pokok transaksi aktif'],
        ['key' => 'gross_profit', 'title' => 'Laba Kotor', 'icon' => 'LK', 'description' => 'Penjualan bersih dikurangi HPP'],
        ['key' => 'approved_expenses', 'title' => 'Pengeluaran', 'icon' => 'PG', 'description' => 'Hanya pengeluaran yang disetujui'],
        ['key' => 'net_profit', 'title' => 'Laba Bersih', 'icon' => 'LB', 'description' => 'Laba kotor dikurangi pengeluaran'],
        ['key' => 'receipt_count', 'title' => 'Jumlah Nota', 'icon' => 'NT', 'description' => 'Nota transaksi aktif'],
    ];
@endphp

<section class="dashboard-financial-grid" aria-label="Ringkasan keuangan">
    @foreach ($financialCards as $card)
        @php($value = $dashboard['cards'][$card['key']])
        <article
            class="dashboard-financial-card card {{ $card['key'] === 'net_profit' && str_starts_with((string) $value['value'], '-') ? 'is-negative' : '' }}"
            data-financial-card="{{ $card['key'] }}"
        >
            <div class="dashboard-financial-card__heading">
                <span class="dashboard-financial-card__icon" aria-hidden="true">{{ $card['icon'] }}</span>
                <h2>{{ $card['title'] }}</h2>
            </div>
            <p class="dashboard-financial-card__value" data-card-value>{{ $value['formatted'] }}</p>
            <p class="dashboard-financial-card__description">{{ $card['description'] }}</p>
        </article>
    @endforeach
</section>
