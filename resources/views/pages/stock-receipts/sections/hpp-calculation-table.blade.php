<section class="table-card receipt-hpp-table" aria-label="Audit harga modal rata-rata">
    <div class="table-card__heading">
        <div><p class="eyebrow">Audit Owner</p><h3>Perubahan Harga Modal Rata-rata</h3></div>
        <span class="badge badge-info">Weighted average</span>
    </div>
    <div class="table-wrapper goods-receipts-desktop-table">
        <table class="table">
            <thead><tr><th>Produk</th><th>Harga modal rata-rata sebelum</th><th>Harga beli masuk</th><th>Harga modal rata-rata sesudah</th></tr></thead>
            <tbody>
                @foreach ($stockReceipt->items as $receiptItem)
                    <tr>
                        <td>{{ $receiptItem->product->code }} - {{ $receiptItem->product->name }}</td>
                        <td>{{ \App\Support\Format\Rupiah::format($receiptItem->average_cost_before) }}</td>
                        <td>{{ \App\Support\Format\Rupiah::format($receiptItem->purchase_price) }}</td>
                        <td><strong>{{ \App\Support\Format\Rupiah::format($receiptItem->average_cost_after) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="receipt-hpp-list" aria-label="Perubahan harga modal rata-rata versi mobile">
        @foreach ($stockReceipt->items as $receiptItem)
            <article class="receipt-hpp-card">
                <header>
                    <p>{{ $receiptItem->product->code }}</p>
                    <h4>{{ $receiptItem->product->name }}</h4>
                </header>
                <dl>
                    <div>
                        <dt>HPP Rata-rata Sebelum</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($receiptItem->average_cost_before) }}</dd>
                    </div>
                    <div>
                        <dt>Harga Beli Masuk</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($receiptItem->purchase_price) }}</dd>
                    </div>
                    <div>
                        <dt>HPP Rata-rata Sesudah</dt>
                        <dd><strong>{{ \App\Support\Format\Rupiah::format($receiptItem->average_cost_after) }}</strong></dd>
                    </div>
                </dl>
            </article>
        @endforeach
    </div>
</section>
