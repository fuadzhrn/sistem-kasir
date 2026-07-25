<section class="card sale-detail-header">
    <div class="sale-detail-header__top">
        <div>
            <span>Nomor Nota</span>
            <h3>{{ $sale->invoice_number }}</h3>
        </div>
        @include('pages.sales.sections.sale-status-badge', ['sale' => $sale])
    </div>
    <dl class="sale-detail-meta">
        <div><dt>Tanggal dan waktu</dt><dd>{{ $sale->transaction_date->locale('id')->translatedFormat('d F Y, H.i') }}</dd></div>
        <div><dt>Cabang</dt><dd>{{ $sale->branch?->code }} — {{ $sale->branch?->name ?? 'Cabang historis' }}</dd></div>
        <div><dt>Kasir</dt><dd>{{ $sale->cashier?->name ?? 'Pengguna historis' }}</dd></div>
        <div><dt>Metode pembayaran</dt><dd>{{ $sale->payment_method_name }}</dd></div>
        <div><dt>Waktu data dibuat</dt><dd>{{ $sale->created_at->locale('id')->translatedFormat('d F Y, H.i') }}</dd></div>
        <div class="sale-detail-meta__notes"><dt>Catatan</dt><dd>{{ $sale->notes ?: 'Tidak ada catatan.' }}</dd></div>
    </dl>
</section>
