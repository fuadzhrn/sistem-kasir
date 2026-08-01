<div class="sales-card-list" aria-label="Daftar transaksi">
    @forelse ($sales as $sale)
        <article class="sales-history-card">
            <header class="sales-history-card__header">
                <div>
                    <span>Nomor Nota</span>
                    <strong class="sales-history-card__invoice">{{ $sale->invoice_number }}</strong>
                </div>
                @include('pages.sales.sections.sale-status-badge', ['sale' => $sale])
            </header>

            <p class="sales-history-card__date">
                {{ $sale->transaction_date->locale('id')->translatedFormat('d F Y') }}
                <span aria-hidden="true">·</span>
                {{ $sale->transaction_date->format('H.i') }}
            </p>

            <dl class="sales-history-card__body">
                <div><dt>Kasir</dt><dd>{{ $sale->cashier?->name ?? 'Pengguna historis' }}</dd></div>
                <div><dt>Cabang</dt><dd>{{ $sale->branch?->name ?? 'Cabang historis' }}</dd></div>
                <div><dt>Metode</dt><dd>{{ $sale->payment_method_name }}</dd></div>
                <div><dt>Jenis item</dt><dd>{{ $sale->items_count }}</dd></div>
                <div class="sales-history-card__total">
                    <dt>Total</dt>
                    <dd>{{ \App\Support\Format\Rupiah::format($sale->total) }}</dd>
                </div>
            </dl>

            <footer class="sales-history-card__footer">
                <a class="btn btn-secondary" href="{{ route('sales.show', $sale) }}">Detail Transaksi</a>
                <form method="POST" action="{{ route('sales.receipt.reprint', $sale) }}" target="receipt-print">
                    @csrf
                    <button class="btn btn-outline" type="submit">Cetak Ulang</button>
                </form>
            </footer>
        </article>
    @empty
        <div class="sales-empty sales-empty--mobile">
            <strong>Belum ada transaksi yang sesuai dengan filter.</strong>
            <p>Ubah rentang atau reset filter untuk melihat riwayat lain.</p>
            <div>
                <a class="btn btn-secondary" href="{{ route('sales.index') }}">Reset Filter</a>
                @can('create', \App\Models\Sale::class)
                    <a class="btn btn-primary" href="{{ route('cashier.index') }}">Menuju Kasir</a>
                @endcan
            </div>
        </div>
    @endforelse
</div>
