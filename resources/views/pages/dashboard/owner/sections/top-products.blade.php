<article class="dashboard-table-card dashboard-table-card--top-products card">
    <header class="dashboard-section-heading">
        <div>
            <h2>Produk Terlaris</h2>
            <p>Peringkat berdasarkan penjualan bersih transaksi aktif.</p>
        </div>
    </header>
    <div class="dashboard-table-wrapper">
        <table class="table dashboard-compact-table dashboard-top-products-table">
            <colgroup>
                <col class="dashboard-top-products-table__rank">
                <col class="dashboard-top-products-table__product">
                <col class="dashboard-top-products-table__quantity">
                <col class="dashboard-top-products-table__receipts">
                <col class="dashboard-top-products-table__sales">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Produk</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Nota</th>
                    <th scope="col">Penjualan Bersih</th>
                </tr>
            </thead>
            <tbody data-top-products>
                @forelse ($dashboard['top_products'] as $product)
                    <tr>
                        <td data-label="Peringkat">#{{ $product['rank'] }}</td>
                        <td data-label="Produk"><strong>{{ $product['name'] }}</strong><small>{{ $product['code'] }}</small></td>
                        <td data-label="Terjual">{{ $product['quantity'] }} {{ $product['unit'] }}</td>
                        <td data-label="Jumlah Nota">{{ $product['receipt_count'] }}</td>
                        <td data-label="Penjualan Bersih">{{ $product['net_sales_formatted'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Belum ada produk terlaris pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
