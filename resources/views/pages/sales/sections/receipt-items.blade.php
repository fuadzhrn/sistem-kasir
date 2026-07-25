<section class="receipt-items" aria-label="Item nota">
    @foreach ($sale->items as $item)
        <article class="receipt-item">
            <div class="receipt-item__name">
                <strong>{{ $item->product_name }}</strong>
                <small>{{ $item->product_code }} · {{ $item->product_size ?: $item->unit_name }}</small>
            </div>
            <div class="receipt-item__calculation">
                <span>{{ \App\Support\Format\Quantity::format($item->quantity) }} × {{ \App\Support\Format\Rupiah::format($item->selling_price) }}</span>
                <strong>{{ \App\Support\Format\Rupiah::format($item->subtotal) }}</strong>
            </div>
            @if ((float) $item->discount_amount > 0)
                <small>Diskon item: {{ \App\Support\Format\Rupiah::format($item->discount_amount) }}</small>
            @endif
        </article>
    @endforeach
</section>
