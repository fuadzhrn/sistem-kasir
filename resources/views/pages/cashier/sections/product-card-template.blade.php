<template id="cashier-product-card-template">
    <article class="cashier-product-card">
        <div class="cashier-product-card__image-wrap">
            <img class="cashier-product-card__image" alt="" loading="lazy" data-product-image>
            <span class="badge" data-product-status></span>
        </div>
        <div class="cashier-product-card__body">
            <p class="cashier-product-card__code" data-product-code></p>
            <h3 data-product-name></h3>
            <p class="cashier-product-card__meta" data-product-meta></p>
            <p class="cashier-product-card__barcode" data-product-barcode></p>
            <div class="cashier-product-card__stock"><span>Stok</span><strong data-product-stock></strong></div>
            <div class="cashier-product-card__footer">
                <strong class="cashier-product-card__price" data-product-price></strong>
                <button class="btn btn-primary" type="button" data-add-product>Tambah</button>
            </div>
        </div>
    </article>
</template>
