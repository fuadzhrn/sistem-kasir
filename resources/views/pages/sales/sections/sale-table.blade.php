<section class="table-card sales-table-card">
    <div class="table-wrapper">
        <table class="table sales-table">
            <thead>
                <tr>
                    <th>Nomor Nota</th>
                    <th>Tanggal dan Waktu</th>
                    <th>Cabang</th>
                    <th>Kasir</th>
                    <th class="table-number">Jenis Item</th>
                    <th class="table-number">Subtotal</th>
                    <th class="table-number">Diskon</th>
                    <th class="table-number">Total</th>
                    <th>Metode Pembayaran</th>
                    <th>Status</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td><strong class="sale-number">{{ $sale->invoice_number }}</strong></td>
                        <td>{{ $sale->transaction_date->locale('id')->translatedFormat('d F Y, H.i') }}</td>
                        <td>{{ $sale->branch?->name ?? 'Cabang historis' }}</td>
                        <td>{{ $sale->cashier?->name ?? 'Pengguna historis' }}</td>
                        <td class="table-number">{{ $sale->items_count }}</td>
                        <td class="table-number">{{ \App\Support\Format\Rupiah::format($sale->subtotal) }}</td>
                        <td class="table-number">{{ \App\Support\Format\Rupiah::format($sale->discount_amount) }}</td>
                        <td class="table-number"><strong>{{ \App\Support\Format\Rupiah::format($sale->total) }}</strong></td>
                        <td>{{ $sale->payment_method_name }}</td>
                        <td>@include('pages.sales.sections.sale-status-badge', ['sale' => $sale])</td>
                        <td class="table-actions">
                            <span class="sales-table__actions">
                                <a class="btn btn-secondary btn-sm" href="{{ route('sales.show', $sale) }}">Detail</a>
                                <a
                                    class="btn btn-outline btn-sm"
                                    href="{{ route('sales.receipt.show', $sale) }}"
                                    target="_blank"
                                    rel="noopener"
                                >Cetak Ulang</a>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="11">
                            <div class="sales-empty">
                                <strong>Belum ada transaksi yang sesuai dengan filter.</strong>
                                <p>Ubah rentang atau reset filter untuk melihat riwayat lain.</p>
                                <div>
                                    <a class="btn btn-secondary btn-sm" href="{{ route('sales.index') }}">Reset Filter</a>
                                    @can('create', \App\Models\Sale::class)
                                        <a class="btn btn-primary btn-sm" href="{{ route('cashier.index') }}">Menuju Kasir</a>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} dari {{ $sales->total() }} transaksi</span>
        <nav class="pagination-buttons" aria-label="Pagination transaksi">
            @if ($sales->onFirstPage())
                <span class="pagination-button" aria-disabled="true">&lsaquo;</span>
            @else
                <a class="pagination-button" href="{{ $sales->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lsaquo;</a>
            @endif
            @foreach ($sales->getUrlRange(max(1, $sales->currentPage() - 2), min($sales->lastPage(), $sales->currentPage() + 2)) as $page => $url)
                <a class="pagination-button {{ $page === $sales->currentPage() ? 'is-active' : '' }}" href="{{ $url }}" @if ($page === $sales->currentPage()) aria-current="page" @endif>{{ $page }}</a>
            @endforeach
            @if ($sales->hasMorePages())
                <a class="pagination-button" href="{{ $sales->nextPageUrl() }}" aria-label="Halaman berikutnya">&rsaquo;</a>
            @else
                <span class="pagination-button" aria-disabled="true">&rsaquo;</span>
            @endif
        </nav>
    </div>
</section>
