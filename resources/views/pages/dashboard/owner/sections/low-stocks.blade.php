<article class="dashboard-table-card dashboard-table-card--low-stocks card">
    <header class="dashboard-section-heading">
        <div>
            <h2>Stok Hampir Habis</h2>
            <p>Kondisi stok saat ini, tidak mengikuti rentang tanggal.</p>
        </div>
    </header>
    <div class="dashboard-table-wrapper">
        <table class="table dashboard-compact-table dashboard-low-stocks-table">
            <colgroup>
                <col class="dashboard-low-stocks-table__branch">
                <col class="dashboard-low-stocks-table__product">
                <col class="dashboard-low-stocks-table__available">
                <col class="dashboard-low-stocks-table__minimum">
                <col class="dashboard-low-stocks-table__status">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Cabang</th>
                    <th scope="col">Produk</th>
                    <th scope="col">Tersedia</th>
                    <th scope="col">Minimum</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody data-low-stocks>
                @forelse ($dashboard['low_stocks'] as $stock)
                    <tr>
                        <td data-label="Cabang">{{ $stock['branch'] }}</td>
                        <td data-label="Produk">
                            @if ($stock['detail_url'])
                                <a href="{{ $stock['detail_url'] }}"><strong>{{ $stock['product_name'] }}</strong></a>
                            @else
                                <strong>{{ $stock['product_name'] }}</strong>
                            @endif
                            <small>{{ $stock['product_code'] }}</small>
                        </td>
                        <td data-label="Tersedia">{{ $stock['quantity'] }} {{ $stock['unit'] }}</td>
                        <td data-label="Minimum">{{ $stock['minimum_stock'] }} {{ $stock['unit'] }}</td>
                        <td data-label="Status"><span class="badge {{ $stock['status'] === 'Habis' ? 'badge-danger' : 'badge-warning' }}">{{ $stock['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Tidak ada stok yang hampir habis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
