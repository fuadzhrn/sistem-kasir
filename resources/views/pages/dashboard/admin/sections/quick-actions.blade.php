<section class="admin-dashboard__quick-actions card" aria-labelledby="admin-quick-actions-title">
    <header class="admin-dashboard__quick-actions-header">
        <div>
            <h2 id="admin-quick-actions-title">Akses Cepat</h2>
            <p>Buka kegiatan utama untuk cabang Anda.</p>
        </div>
    </header>

    <div class="admin-dashboard__quick-actions-grid">
        @can('viewAny', \App\Models\Product::class)
            <a class="admin-dashboard__quick-action" href="{{ route('products.index') }}">
                <span class="admin-dashboard__quick-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M4 6.5 12 3l8 3.5v11L12 21l-8-3.5v-11Zm8-1.3L7.2 7.3 12 9.4l4.8-2.1L12 5.2Zm-6 3.6v7.4l5 2.2V11L6 8.8Zm7 9.6 5-2.2V8.8L13 11v7.4Z"/>
                    </svg>
                </span>
                <span>
                    <strong>Produk</strong>
                    <small>Kelola produk cabang</small>
                </span>
            </a>
        @endcan

        @can('viewAny', \App\Models\BranchStock::class)
            <a class="admin-dashboard__quick-action" href="{{ route('stocks.index') }}">
                <span class="admin-dashboard__quick-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M3 5h18v4H3V5Zm2 6h14v8H5v-8Zm5 2v2h4v-2h-4Z"/>
                    </svg>
                </span>
                <span>
                    <strong>Stok</strong>
                    <small>Periksa stok cabang</small>
                </span>
            </a>
        @endcan

        @can('viewAny', \App\Models\StockReceipt::class)
            <a class="admin-dashboard__quick-action" href="{{ route('stock-receipts.index') }}">
                <span class="admin-dashboard__quick-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M4 4h10v4h6v12H4V4Zm2 2v12h12v-8h-6V6H6Zm3 5h2V9h2v2h2v2h-2v2h-2v-2H9v-2Z"/>
                    </svg>
                </span>
                <span>
                    <strong>Barang Masuk</strong>
                    <small>Catat penerimaan barang</small>
                </span>
            </a>
        @endcan

        @can('viewAny', \App\Models\Expense::class)
            <a class="admin-dashboard__quick-action" href="{{ route('expenses.index') }}">
                <span class="admin-dashboard__quick-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 3h8v2H8v-2Zm0 4h5v2H8v-2Z"/>
                    </svg>
                </span>
                <span>
                    <strong>Pengeluaran</strong>
                    <small>Kelola biaya cabang</small>
                </span>
            </a>
        @endcan
    </div>
</section>
