@php
    $selectedProductId = (string) ($item['product_id'] ?? '');
    $selectedProduct = $products->first(fn ($product) => (string) $product->id === $selectedProductId);
@endphp
<tr data-receipt-item-row>
    <td class="receipt-product-cell">
        <select class="form-select" name="items[{{ $rowIndex }}][product_id]" required data-product-select>
            <option value="">Pilih produk</option>
            @foreach ($products as $productOption)
                @php
                    $productLabel = $productOption->code.' - '.$productOption->name
                        .($productOption->brand ? ' / '.$productOption->brand : '')
                        .($productOption->size ? ' / '.$productOption->size : '');
                @endphp
                <option
                    value="{{ $productOption->id }}"
                    data-code="{{ $productOption->code }}"
                    data-size="{{ $productOption->size ?: '-' }}"
                    data-unit="{{ $productOption->unit->symbol ?: $productOption->unit->name }}"
                    data-search="{{ mb_strtolower($productLabel) }}"
                    @selected((string) $productOption->id === $selectedProductId)
                >{{ $productLabel }}</option>
            @endforeach
        </select>
        @error('items.'.$rowIndex.'.product_id')<span class="form-error">{{ $message }}</span>@enderror
    </td>
    <td data-product-code>{{ $selectedProduct?->code ?? '-' }}</td>
    <td data-product-size>{{ $selectedProduct?->size ?: '-' }}</td>
    <td data-product-unit>{{ $selectedProduct ? ($selectedProduct->unit->symbol ?: $selectedProduct->unit->name) : '-' }}</td>
    <td class="receipt-number-cell">
        <input class="form-control" name="items[{{ $rowIndex }}][quantity]" type="number" value="{{ $item['quantity'] ?? '' }}" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" required data-item-quantity>
        @error('items.'.$rowIndex.'.quantity')<span class="form-error">{{ $message }}</span>@enderror
    </td>
    <td class="receipt-money-cell">
        <div class="input-group">
            <span class="input-group__addon">Rp</span>
            <input class="form-control" name="items[{{ $rowIndex }}][purchase_price]" type="text" value="{{ \App\Support\Format\Rupiah::input($item['purchase_price'] ?? null) }}" inputmode="numeric" autocomplete="off" required data-item-price data-rupiah-input>
        </div>
        @error('items.'.$rowIndex.'.purchase_price')<span class="form-error">{{ $message }}</span>@enderror
    </td>
    <td><strong data-item-subtotal>Rp0</strong></td>
    <td><button class="btn btn-sm btn-danger" type="button" data-remove-receipt-item aria-label="Hapus baris produk">Hapus</button></td>
</tr>
