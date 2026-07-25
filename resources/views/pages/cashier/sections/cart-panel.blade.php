<section
    class="cashier-cart-panel"
    id="cashier-cart-panel"
    role="tabpanel"
    aria-labelledby="cashier-cart-tab"
    data-cashier-panel="cart"
    tabindex="-1"
>
    <div class="cashier-cart-heading">
        <div>
            <p class="eyebrow">Transaksi sementara</p>
            <h2 tabindex="-1" data-cart-heading>Keranjang</h2>
        </div>
        <div class="cashier-cart-heading__actions">
            <span class="badge badge-info"><span data-cart-kind-count>0</span> jenis</span>
            <button class="btn btn-sm btn-outline" type="button" data-modal-open="cashier-clear-cart-modal" data-clear-cart-trigger disabled>Kosongkan</button>
        </div>
    </div>
    @include('pages.cashier.sections.cart-items')
    @include('pages.cashier.sections.cart-summary')
    @include('pages.cashier.sections.payment-form')
</section>
