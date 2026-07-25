<div class="cashier-product-filters">
    <div class="cashier-search">
        <label class="form-label" for="cashier-product-search">Cari Produk</label>
        <div class="cashier-search__control">
            <input
                class="form-control"
                id="cashier-product-search"
                type="search"
                maxlength="100"
                placeholder="Cari nama, kode, atau barcode"
                autocomplete="off"
                enterkeyhint="search"
                data-product-search
                @disabled($branch === null)
            >
            <button class="cashier-search__clear" type="button" aria-label="Bersihkan pencarian produk" data-search-clear hidden>×</button>
        </div>
    </div>
    <div class="cashier-category-filter" aria-label="Filter kategori">
        <button class="cashier-category-pill is-active" type="button" data-category-id="" aria-pressed="true" @disabled($branch === null)>Semua Kategori</button>
        @foreach ($categories as $category)
            <button class="cashier-category-pill" type="button" data-category-id="{{ $category->id }}" aria-pressed="false" @disabled($branch === null)>{{ $category->name }}</button>
        @endforeach
    </div>
</div>
