<section class="table-card sale-items">
    <div class="table-toolbar">
        <div>
            <h3>Item Transaksi</h3>
            <p>Data produk menggunakan snapshot pada saat transaksi.</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th>Ukuran / Satuan</th>
                    <th class="table-number">Quantity</th>
                    <th class="table-number">Harga Jual</th>
                    <th class="table-number">Diskon Item</th>
                    <th class="table-number">Subtotal Bersih</th>
                    @if ($showInternal)
                        <th class="table-number">HPP</th>
                        <th class="table-number">Profit</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->product_code }}</td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td>{{ $item->product_size ?: '—' }} / {{ $item->unit_name }}</td>
                        <td class="table-number">{{ \App\Support\Format\Quantity::format($item->quantity) }}</td>
                        <td class="table-number">{{ \App\Support\Format\Rupiah::format($item->selling_price) }}</td>
                        <td class="table-number">{{ \App\Support\Format\Rupiah::format($item->discount_amount) }}</td>
                        <td class="table-number"><strong>{{ \App\Support\Format\Rupiah::format($item->subtotal) }}</strong></td>
                        @if ($showInternal)
                            <td class="table-number">{{ \App\Support\Format\Rupiah::format($item->cost_price) }}</td>
                            <td class="table-number">{{ \App\Support\Format\Rupiah::format($item->profit) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
