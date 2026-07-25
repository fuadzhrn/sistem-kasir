<section class="card receipt-detail-header">
    <div class="receipt-detail-header__title">
        <div>
            <p class="eyebrow">Nomor penerimaan</p>
            <h3>{{ $stockReceipt->receipt_number }}</h3>
        </div>
        <span class="badge badge-success">Final</span>
    </div>
    <dl class="receipt-detail-grid">
        <div><dt>Tanggal penerimaan</dt><dd>{{ $stockReceipt->receipt_date->format('d F Y') }}</dd></div>
        <div><dt>Cabang</dt><dd>{{ $stockReceipt->branch->code }} - {{ $stockReceipt->branch->name }}</dd></div>
        <div><dt>Supplier</dt><dd>{{ $stockReceipt->supplier_name ?: 'Tidak dicantumkan' }}</dd></div>
        <div><dt>Dibuat oleh</dt><dd>{{ $stockReceipt->creator?->name ?? 'Pengguna tidak tersedia' }}</dd></div>
        <div><dt>Waktu input</dt><dd>{{ $stockReceipt->created_at->format('d/m/Y H:i:s') }}</dd></div>
        <div><dt>Jumlah produk</dt><dd>{{ $stockReceipt->items->count() }} produk</dd></div>
        <div><dt>Total biaya</dt><dd><strong>{{ \App\Support\Format\Rupiah::format($stockReceipt->total_cost) }}</strong></dd></div>
        <div class="receipt-detail-grid__full"><dt>Catatan</dt><dd>{{ $stockReceipt->notes ?: 'Tidak ada catatan' }}</dd></div>
    </dl>
</section>
