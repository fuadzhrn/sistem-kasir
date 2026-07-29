@php
    $selectedProductId = (string) ($item['product_id'] ?? '');
    $selectedProduct = $products->first(fn ($product) => (string) $product->id === $selectedProductId);
@endphp
<tr data-receipt-item-row>
    <td class="receipt-product-cell" data-label="Produk">
        <h4 class="receipt-item-sequence" data-item-sequence>Item</h4>
        <label class="receipt-item-field-label" for="receipt-product-{{ $rowIndex }}">Produk</label>
        <select
            class="form-select"
            id="receipt-product-{{ $rowIndex }}"
            name="items[{{ $rowIndex }}][product_id]"
            required
            data-product-select
            @error('items.'.$rowIndex.'.product_id') aria-describedby="receipt-product-error-{{ $rowIndex }}" aria-invalid="true" @enderror
        >
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
        @error('items.'.$rowIndex.'.product_id')<span class="form-error" id="receipt-product-error-{{ $rowIndex }}">{{ $message }}</span>@enderror
    </td>
    <td data-label="Kode" data-product-code>{{ $selectedProduct?->code ?? '-' }}</td>
    <td data-label="Ukuran" data-product-size>{{ $selectedProduct?->size ?: '-' }}</td>
    <td data-label="Satuan" data-product-unit>{{ $selectedProduct ? ($selectedProduct->unit->symbol ?: $selectedProduct->unit->name) : '-' }}</td>
    <td class="receipt-number-cell" data-label="Quantity">
        <label class="receipt-item-field-label" for="receipt-quantity-{{ $rowIndex }}">Quantity</label>
        <input
            class="form-control"
            id="receipt-quantity-{{ $rowIndex }}"
            name="items[{{ $rowIndex }}][quantity]"
            type="text"
            value="{{ \App\Support\Format\Quantity::inputValue($item['quantity'] ?? '') }}"
            inputmode="decimal"
            required
            data-item-quantity
            data-quantity-input
            @error('items.'.$rowIndex.'.quantity') aria-describedby="receipt-quantity-error-{{ $rowIndex }}" aria-invalid="true" @enderror
        >
        @error('items.'.$rowIndex.'.quantity')<span class="form-error" id="receipt-quantity-error-{{ $rowIndex }}">{{ $message }}</span>@enderror
    </td>
    <td class="receipt-money-cell" data-label="Harga Modal">
        <label class="receipt-item-field-label" for="receipt-price-{{ $rowIndex }}">Harga Modal</label>
        <div class="input-group">
            <span class="input-group__addon">Rp</span>
            <input
                class="form-control"
                id="receipt-price-{{ $rowIndex }}"
                name="items[{{ $rowIndex }}][purchase_price]"
                type="text"
                value="{{ \App\Support\Format\Rupiah::input($item['purchase_price'] ?? null) }}"
                inputmode="numeric"
                autocomplete="off"
                required
                data-item-price
                data-rupiah-input
                @error('items.'.$rowIndex.'.purchase_price') aria-describedby="receipt-price-error-{{ $rowIndex }}" aria-invalid="true" @enderror
            >
        </div>
        @error('items.'.$rowIndex.'.purchase_price')<span class="form-error" id="receipt-price-error-{{ $rowIndex }}">{{ $message }}</span>@enderror
    </td>
    <td class="receipt-item-subtotal" data-label="Subtotal"><strong data-item-subtotal>Rp0</strong></td>
    <td class="receipt-item-remove" data-label="Tindakan">
        <button class="btn btn-sm btn-danger" type="button" data-remove-receipt-item aria-label="Hapus item produk">
            Hapus Item
        </button>
    </td>
</tr>
