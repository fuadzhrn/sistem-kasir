<section class="cashier-dashboard__history card" aria-labelledby="cashier-history-title">
    <header class="cashier-dashboard__section-heading">
        <div>
            <p class="cashier-dashboard__section-eyebrow">Aktivitas Kasir</p>
            <h2 id="cashier-history-title">Riwayat Transaksi Terbaru</h2>
            <p>Hanya transaksi yang dibuat menggunakan akun Anda.</p>
        </div>
        <span class="cashier-dashboard__history-count">
            {{ number_format($dashboard['sales']->total(), 0, ',', '.') }} nota
        </span>
    </header>

    @if ($dashboard['sales']->isEmpty())
        @include('pages.dashboard.cashier.sections.transaction-empty-state')
    @else
        <div class="table-responsive cashier-dashboard__desktop-history">
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
                                    <form method="POST" action="{{ $sale['receipt_url'] }}" target="receipt-print">
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

        <div class="cashier-dashboard__transactions" aria-label="Daftar transaksi terbaru">
            @foreach ($dashboard['sales'] as $sale)
                <article class="cashier-dashboard__transaction-card">
                    <header class="cashier-dashboard__transaction-header">
                        <div>
                            <strong>{{ $sale['invoice_number'] }}</strong>
                            <time>{{ $sale['transaction_date'] }}</time>
                        </div>
                        @include('pages.dashboard.cashier.sections.transaction-status-badge', ['sale' => $sale])
                    </header>

                    <dl class="cashier-dashboard__transaction-body">
                        <div class="cashier-dashboard__transaction-total">
                            <dt>Total</dt>
                            <dd>{{ $sale['total_formatted'] }}</dd>
                        </div>
                        <div>
                            <dt>Pembayaran</dt>
                            <dd>{{ $sale['payment_method'] }}</dd>
                        </div>
                        <div>
                            <dt>Jumlah Item</dt>
                            <dd>{{ number_format($sale['items_count'], 0, ',', '.') }}</dd>
                        </div>
                    </dl>

                    <footer class="cashier-dashboard__transaction-footer">
                        <a class="btn btn-secondary" href="{{ $sale['detail_url'] }}">Detail</a>
                        <form method="POST" action="{{ $sale['receipt_url'] }}" target="receipt-print">
                            @csrf
                            <button class="btn btn-outline" type="submit">Cetak Ulang</button>
                        </form>
                    </footer>
                </article>
            @endforeach
        </div>

        {{ $dashboard['sales']->onEachSide(1)->links('components.pagination', ['itemLabel' => 'transaksi']) }}
    @endif

    <div class="cashier-dashboard__history-footer">
        <a class="btn btn-secondary cashier-dashboard__view-history" href="{{ route('sales.index') }}">
            Lihat Semua Riwayat
        </a>
    </div>
</section>
