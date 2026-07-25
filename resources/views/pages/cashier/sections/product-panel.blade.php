<section
    class="cashier-products-panel is-active"
    id="cashier-products-panel"
    role="tabpanel"
    aria-labelledby="cashier-products-tab"
    data-cashier-panel="products"
>
    <div class="cashier-panel-heading">
        <div>
            <p class="eyebrow">Katalog cabang</p>
            <h2>Produk</h2>
        </div>
        <span class="cashier-result-count" data-product-count>0 produk</span>
    </div>
    @include('pages.cashier.sections.product-filters')
    @include('pages.cashier.sections.product-loading')
    @include('pages.cashier.sections.product-empty-state')
    @include('pages.cashier.sections.product-grid')
</section>
