<template id="cashier-cart-item-template">
    <article class="cashier-cart-item">
        <img class="cashier-cart-item__image" alt="" loading="lazy" data-cart-image>
        <div class="cashier-cart-item__content">
            <div class="cashier-cart-item__heading">
                <div><strong data-cart-name></strong><span data-cart-meta></span></div>
                <button class="cashier-cart-item__remove" type="button" data-cart-remove>Hapus</button>
            </div>
            <div class="cashier-cart-item__pricing">
                <span data-cart-price></span>
                <strong data-cart-subtotal></strong>
            </div>
            <div class="cashier-quantity-control">
                <button type="button" data-quantity-decrease>−</button>
                <input type="number" min="0.001" step="0.001" inputmode="decimal" data-cart-quantity>
                <button type="button" data-quantity-increase>+</button>
            </div>
            <small data-cart-stock></small>
        </div>
    </article>
</template>
