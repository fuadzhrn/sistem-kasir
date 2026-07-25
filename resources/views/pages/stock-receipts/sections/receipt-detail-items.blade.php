<section class="table-card receipt-detail-table" aria-label="Item penerimaan">
    <div class="table-card__heading">
        <div><p class="eyebrow">Rincian dokumen</p><h3>Produk Diterima</h3></div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Kode</th><th>Produk</th><th>Ukuran</th><th>Satuan</th><th>Quantity masuk</th><th>Harga beli penerimaan</th><th>Subtotal</th><th>Stok sebelum</th><th>Stok sesudah</th></tr></thead>
            <tbody>
                @foreach ($stockReceipt->items as $receiptItem)
                    <tr>
                        <td><strong>{{ $receiptItem->product->code }}</strong></td>
                        <td>{{ $receiptItem->product->name }} @if(! $receiptItem->product->is_active)<span class="badge badge-danger">Nonaktif</span>@endif</td>
                        <td>{{ $receiptItem->product->size ?: '-' }}</td>
                        <td>{{ $receiptItem->product->unit->symbol ?: $receiptItem->product->unit->name }}</td>
                        <td>{{ \App\Support\Format\Quantity::format($receiptItem->quantity) }}</td>
                        <td>{{ \App\Support\Format\Rupiah::format($receiptItem->purchase_price) }}</td>
                        <td><strong>{{ \App\Support\Format\Rupiah::format($receiptItem->subtotal) }}</strong></td>
                        <td>{{ \App\Support\Format\Quantity::format($receiptItem->quantity_before) }}</td>
                        <td>{{ \App\Support\Format\Quantity::format($receiptItem->quantity_after) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><th colspan="6">Total biaya penerimaan</th><th>{{ \App\Support\Format\Rupiah::format($stockReceipt->total_cost) }}</th><th colspan="2"></th></tr></tfoot>
        </table>
    </div>
</section>
