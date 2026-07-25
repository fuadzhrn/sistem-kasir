@php
    $statusClass = match ($stockTransfer->status) {
        \App\Models\StockTransfer::STATUS_COMPLETED => 'badge-success',
        \App\Models\StockTransfer::STATUS_REJECTED => 'badge-danger',
        \App\Models\StockTransfer::STATUS_CANCELLED => 'badge-neutral',
        default => 'badge-warning',
    };
@endphp
<section class="card transfer-detail">
    <div class="transfer-detail__heading">
        <div><p class="eyebrow">Nomor mutasi</p><h3>{{ $stockTransfer->transfer_number }}</h3></div>
        <span class="badge {{ $statusClass }}">{{ $stockTransfer->status_label }}</span>
    </div>
    <dl class="transfer-detail-grid">
        <div><dt>Cabang asal</dt><dd>{{ $stockTransfer->sourceBranch->code }} - {{ $stockTransfer->sourceBranch->name }}</dd></div>
        <div><dt>Cabang tujuan</dt><dd>{{ $stockTransfer->destinationBranch->code }} - {{ $stockTransfer->destinationBranch->name }}</dd></div>
        <div><dt>Produk</dt><dd>{{ $stockTransfer->product->code }} - {{ $stockTransfer->product->name }}</dd></div>
        <div><dt>Ukuran / satuan</dt><dd>{{ $stockTransfer->product->size ?: '-' }} / {{ $stockTransfer->product->unit->symbol ?: $stockTransfer->product->unit->name }}</dd></div>
        <div><dt>Quantity</dt><dd><strong>{{ \App\Support\Format\Quantity::format($stockTransfer->quantity) }}</strong></dd></div>
        <div><dt>Waktu permintaan</dt><dd>{{ $stockTransfer->created_at->format('d/m/Y H:i:s') }}</dd></div>
        <div><dt>Diminta oleh</dt><dd>{{ $stockTransfer->requester?->name ?? 'Pengguna tidak tersedia' }}</dd></div>
        <div><dt>Diproses oleh</dt><dd>{{ $stockTransfer->reviewer?->name ?? '-' }}</dd></div>
        @if ($stockTransfer->reviewed_at)
            <div><dt>Waktu pemrosesan</dt><dd>{{ $stockTransfer->reviewed_at->format('d/m/Y H:i:s') }}</dd></div>
        @endif
        @if ($stockTransfer->status === \App\Models\StockTransfer::STATUS_COMPLETED)
            <div><dt>Stok asal sebelum / sesudah</dt><dd>{{ \App\Support\Format\Quantity::format($stockTransfer->source_quantity_before) }} / {{ \App\Support\Format\Quantity::format($stockTransfer->source_quantity_after) }}</dd></div>
            <div><dt>Stok tujuan sebelum / sesudah</dt><dd>{{ \App\Support\Format\Quantity::format($stockTransfer->destination_quantity_before) }} / {{ \App\Support\Format\Quantity::format($stockTransfer->destination_quantity_after) }}</dd></div>
            @if (auth()->user()->isOwner())
                <div><dt>Average cost asal</dt><dd>{{ \App\Support\Format\Rupiah::format($stockTransfer->unit_cost) }}</dd></div>
                <div><dt>Average cost tujuan sebelum / sesudah</dt><dd>{{ \App\Support\Format\Rupiah::format($stockTransfer->destination_average_cost_before) }} / {{ \App\Support\Format\Rupiah::format($stockTransfer->destination_average_cost_after) }}</dd></div>
            @endif
        @endif
        <div class="transfer-detail-grid__full"><dt>Catatan</dt><dd>{{ $stockTransfer->notes }}</dd></div>
        @if ($stockTransfer->rejection_reason)
            <div class="transfer-detail-grid__full"><dt>{{ $stockTransfer->status === \App\Models\StockTransfer::STATUS_REJECTED ? 'Alasan penolakan' : 'Alasan pembatalan' }}</dt><dd>{{ $stockTransfer->rejection_reason }}</dd></div>
        @endif
    </dl>
</section>
