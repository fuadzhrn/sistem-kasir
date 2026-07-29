<section class="card adjustment-detail">
    <div class="adjustment-detail__heading">
        <div><p class="eyebrow">Nomor penyesuaian</p><h3>{{ $stockAdjustment->adjustment_number }}</h3></div>
        <span class="badge {{ $stockAdjustment->quantity_change > 0 ? 'badge-success' : 'badge-warning' }}">{{ $stockAdjustment->type_label }}</span>
    </div>
    <dl class="adjustment-detail-grid">
        <div><dt>Cabang</dt><dd>{{ $stockAdjustment->branch->code }} - {{ $stockAdjustment->branch->name }}</dd></div>
        <div><dt>Produk</dt><dd>{{ $stockAdjustment->product->code }} - {{ $stockAdjustment->product->name }}</dd></div>
        <div><dt>Ukuran / satuan</dt><dd>{{ $stockAdjustment->product->size ?: '-' }} / {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd></div>
        <div><dt>Waktu pencatatan</dt><dd>{{ $stockAdjustment->created_at->locale('id')->translatedFormat('d F Y · H.i') }}</dd></div>
        <div>
            <dt>Quantity penyesuaian</dt>
            <dd>{{ \App\Support\Format\Quantity::format($stockAdjustment->quantity) }} {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd>
        </div>
        @if ($stockAdjustment->adjustment_type === \App\Models\StockAdjustment::TYPE_CORRECTION)
            <div>
                <dt>Target quantity</dt>
                <dd>{{ \App\Support\Format\Quantity::format($stockAdjustment->target_quantity) }} {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd>
            </div>
        @endif
        <div class="adjustment-detail-stock">
            <dt>Stok sebelum</dt>
            <dd>{{ \App\Support\Format\Quantity::format($stockAdjustment->quantity_before) }} {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd>
        </div>
        <div class="adjustment-detail-stock">
            <dt>Perubahan</dt>
            <dd class="{{ $stockAdjustment->quantity_change > 0 ? 'adjustment-change--in' : 'adjustment-change--out' }}">{{ \App\Support\Format\Quantity::signed($stockAdjustment->quantity_change) }} {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd>
        </div>
        <div class="adjustment-detail-stock adjustment-detail-stock--after">
            <dt>Stok sesudah</dt>
            <dd><strong>{{ \App\Support\Format\Quantity::format($stockAdjustment->quantity_after) }}</strong> {{ $stockAdjustment->product->unit->symbol ?: $stockAdjustment->product->unit->name }}</dd>
        </div>
        @if (auth()->user()->isOwner())
            <div><dt>Biaya modal per unit saat penyesuaian</dt><dd>{{ \App\Support\Format\Rupiah::format($stockAdjustment->unit_cost) }}</dd></div>
        @endif
        <div><dt>Dibuat oleh</dt><dd>{{ $stockAdjustment->creator?->name ?? 'Pengguna tidak tersedia' }}</dd></div>
        <div class="adjustment-detail-grid__full"><dt>Alasan</dt><dd>{{ $stockAdjustment->reason }}</dd></div>
    </dl>
</section>
