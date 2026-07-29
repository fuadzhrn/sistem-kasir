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
                        <td data-label="Nomor Nota"><a href="{{ $sale['detail_url'] }}"><strong>{{ $sale['invoice_number'] }}</strong></a></td>
                        <td data-label="Tanggal dan Waktu"><time datetime="{{ $sale['transaction_date_iso'] }}">{{ $sale['transaction_date'] }}</time></td>
                        <td data-label="Cabang">{{ $sale['branch'] }}</td>
                        <td data-label="Kasir">{{ $sale['cashier'] }}</td>
                        <td data-label="Pembayaran">{{ $sale['payment_method'] }}</td>
                        <td data-label="Total">{{ $sale['total_formatted'] }}</td>
                        <td data-label="Status">
                            <span class="badge badge-{{ $sale['status_variant'] }}">{{ $sale['status'] }}</span>
                            <a class="dashboard-mobile-detail" href="{{ $sale['detail_url'] }}">Lihat Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">Belum ada transaksi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
