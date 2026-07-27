@php
    $visibilityOptions = [
        'show_logo' => ['receipt.show_logo', 'Logo toko'],
        'show_store_address' => ['receipt.show_store_address', 'Alamat toko sebagai fallback'],
        'show_store_phone' => ['receipt.show_store_phone', 'Telepon toko sebagai fallback'],
        'show_branch_address' => ['receipt.show_branch_address', 'Alamat cabang'],
        'show_branch_phone' => ['receipt.show_branch_phone', 'Telepon cabang'],
        'show_product_code' => ['receipt.show_product_code', 'Kode produk'],
        'show_transaction_notes' => ['receipt.show_transaction_notes', 'Catatan transaksi'],
        'show_copy_label' => ['receipt.show_copy_label', 'Label SALINAN'],
    ];
@endphp
<fieldset class="settings-fieldset">
    <legend>Informasi Opsional</legend>
    <div class="settings-toggle-grid">
        @foreach ($visibilityOptions as $field => [$key, $label])
            <label class="settings-toggle">
                <input type="checkbox" name="{{ $field }}" value="1" @checked((bool) old($field, $settings[$key])) data-preview-toggle="{{ $field }}">
                <span><strong>{{ $label }}</strong><small>Dapat ditampilkan atau disembunyikan.</small></span>
            </label>
        @endforeach
    </div>
</fieldset>
