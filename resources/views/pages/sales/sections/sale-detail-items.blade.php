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

    <div class="sale-item-cards" aria-label="Item transaksi">
        @foreach ($sale->items as $item)
            <article class="sale-item-card">
                <header class="sale-item-card__header">
                    <div>
                        <span>{{ $item->product_code }}</span>
                        <h4>{{ $item->product_name }}</h4>
                    </div>
                    <span>{{ $item->product_size ?: 'Tanpa ukuran' }} · {{ $item->unit_name }}</span>
                </header>
                <dl>
                    <div><dt>Harga satuan</dt><dd>{{ \App\Support\Format\Rupiah::format($item->selling_price) }}</dd></div>
                    <div>
                        <dt>Quantity</dt>
                        <dd>{{ \App\Support\Format\Quantity::format($item->quantity) }} {{ $item->unit_name }}</dd>
                    </div>
                    <div><dt>Diskon item</dt><dd>{{ \App\Support\Format\Rupiah::format($item->discount_amount) }}</dd></div>
                    <div class="sale-item-card__subtotal">
                        <dt>Subtotal bersih</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($item->subtotal) }}</dd>
                    </div>
                    @if ($showInternal)
                        <div><dt>HPP</dt><dd>{{ \App\Support\Format\Rupiah::format($item->cost_price) }}</dd></div>
                        <div><dt>Profit</dt><dd>{{ \App\Support\Format\Rupiah::format($item->profit) }}</dd></div>
                    @endif
                </dl>
            </article>
        @endforeach
    </div>
</section>
