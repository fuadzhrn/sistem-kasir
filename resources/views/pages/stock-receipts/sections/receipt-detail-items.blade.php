<section class="table-card receipt-detail-table" aria-label="Item penerimaan">
    <div class="table-card__heading">
        <div><p class="eyebrow">Rincian dokumen</p><h3>Produk Diterima</h3></div>
    </div>
    <div class="table-wrapper goods-receipts-desktop-table">
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

    <div class="receipt-detail-items-list" aria-label="Produk diterima versi mobile">
        @foreach ($stockReceipt->items as $receiptItem)
            <article class="receipt-detail-item">
                <header class="receipt-detail-item__header">
                    <div>
                        <p class="receipt-detail-item__code">{{ $receiptItem->product->code }}</p>
                        <h4>{{ $receiptItem->product->name }}</h4>
                    </div>
                    @if (! $receiptItem->product->is_active)
                        <span class="badge badge-danger">Nonaktif</span>
                    @endif
                </header>
                <dl class="receipt-detail-item__body">
                    <div>
                        <dt>Ukuran</dt>
                        <dd>{{ $receiptItem->product->size ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Satuan</dt>
                        <dd>{{ $receiptItem->product->unit->symbol ?: $receiptItem->product->unit->name }}</dd>
                    </div>
                    <div>
                        <dt>Quantity Masuk</dt>
                        <dd class="receipt-detail-item__quantity">
                            {{ \App\Support\Format\Quantity::format($receiptItem->quantity) }}
                            {{ $receiptItem->product->unit->symbol ?: $receiptItem->product->unit->name }}
                        </dd>
                    </div>
                    <div>
                        <dt>Harga Modal</dt>
                        <dd class="receipt-detail-item__money">
                            {{ \App\Support\Format\Rupiah::format($receiptItem->purchase_price) }}
                        </dd>
                    </div>
                    <div class="receipt-detail-item__subtotal">
                        <dt>Subtotal</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($receiptItem->subtotal) }}</dd>
                    </div>
                    <div>
                        <dt>Stok Sebelum</dt>
                        <dd>
                            {{ \App\Support\Format\Quantity::format($receiptItem->quantity_before) }}
                            {{ $receiptItem->product->unit->symbol ?: $receiptItem->product->unit->name }}
                        </dd>
                    </div>
                    <div>
                        <dt>Stok Sesudah</dt>
                        <dd>
                            {{ \App\Support\Format\Quantity::format($receiptItem->quantity_after) }}
                            {{ $receiptItem->product->unit->symbol ?: $receiptItem->product->unit->name }}
                        </dd>
                    </div>
                </dl>
            </article>
        @endforeach

        <aside class="receipt-detail-total">
            <span>Total Biaya Penerimaan</span>
            <strong>{{ \App\Support\Format\Rupiah::format($stockReceipt->total_cost) }}</strong>
        </aside>
    </div>
</section>
