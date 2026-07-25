<article class="dashboard-table-card card dashboard-table-card--wide">
    <header class="dashboard-section-heading">
        <div>
            <h2>Transaksi Terbaru</h2>
            <p>Termasuk transaksi selesai dan dibatalkan dalam periode aktif.</p>
        </div>
        <a href="{{ route('sales.index') }}">Lihat riwayat</a>
    </header>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Nota</th>
                    <th scope="col">Waktu</th>
                    <th scope="col">Cabang</th>
                    <th scope="col">Kasir</th>
                    <th scope="col">Pembayaran</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody data-latest-transactions>
                @forelse ($dashboard['latest_transactions'] as $sale)
                    <tr>
                        <td><a href="{{ $sale['detail_url'] }}"><strong>{{ $sale['invoice_number'] }}</strong></a></td>
                        <td><time datetime="{{ $sale['transaction_date_iso'] }}">{{ $sale['transaction_date'] }}</time></td>
                        <td>{{ $sale['branch'] }}</td>
                        <td>{{ $sale['cashier'] }}</td>
                        <td>{{ $sale['payment_method'] }}</td>
                        <td>{{ $sale['total_formatted'] }}</td>
                        <td><span class="badge badge-{{ $sale['status_variant'] }}">{{ $sale['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">Belum ada transaksi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
