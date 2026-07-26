<section class="cashier-dashboard__history card" aria-labelledby="cashier-history-title">
    <header class="cashier-dashboard__section-heading">
        <div>
            <h2 id="cashier-history-title">Riwayat Transaksi Saya</h2>
            <p>Hanya transaksi yang dibuat menggunakan akun Anda.</p>
        </div>
        <span>{{ number_format($dashboard['sales']->total(), 0, ',', '.') }} nota</span>
    </header>

    @if ($dashboard['sales']->isEmpty())
        @include('pages.dashboard.cashier.sections.transaction-empty-state')
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Nomor Nota</th>
                        <th scope="col">Tanggal dan Waktu</th>
                        <th scope="col">Jumlah Item</th>
                        <th scope="col">Total</th>
                        <th scope="col">Metode Pembayaran</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dashboard['sales'] as $sale)
                        <tr>
                            <td><strong>{{ $sale['invoice_number'] }}</strong></td>
                            <td>{{ $sale['transaction_date'] }}</td>
                            <td>{{ number_format($sale['items_count'], 0, ',', '.') }}</td>
                            <td>{{ $sale['total_formatted'] }}</td>
                            <td>{{ $sale['payment_method'] }}</td>
                            <td>
                                @include('pages.dashboard.cashier.sections.transaction-status-badge', ['sale' => $sale])
                            </td>
                            <td>
                                <div class="cashier-dashboard__row-actions">
                                    <a class="btn btn-secondary btn-sm" href="{{ $sale['detail_url'] }}">Detail</a>
                                    <form method="POST" action="{{ $sale['receipt_url'] }}" target="_blank" rel="noopener">
                                        @csrf
                                        <button class="btn btn-ghost btn-sm" type="submit">Cetak Ulang</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="cashier-dashboard__pagination">
            {{ $dashboard['sales']->links() }}
        </div>
    @endif
</section>
