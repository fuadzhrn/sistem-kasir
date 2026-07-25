import { calculateCartSummary, calculateLineSubtotal } from './payment-calculator.js';
import { formatQuantity, formatRupiah, moneyToCents } from './cashier-utils.js';

export function createCartRenderer(root, store, options = {}) {
    const container = root.querySelector('[data-cart-items]');
    const template = document.getElementById('cashier-cart-item-template');
    const emptyState = root.querySelector('[data-cart-empty]');
    const clearTrigger = root.querySelector('[data-clear-cart-trigger]');
    const mobileBar = root.querySelector('[data-mobile-cart-bar]');

    function render(items) {
        const fragment = document.createDocumentFragment();

        items.forEach(function (item) {
            const card = template.content.firstElementChild.cloneNode(true);
            const productId = String(item.product_id);
            const quantityInput = card.querySelector('[data-cart-quantity]');
            card.dataset.productId = productId;
            card.querySelector('[data-cart-image]').src = item.image_url;
            card.querySelector('[data-cart-image]').alt = 'Foto ' + item.name;
            card.querySelector('[data-cart-name]').textContent = item.name;
            card.querySelector('[data-cart-meta]').textContent = [item.code, item.size, item.unit_name].filter(Boolean).join(' • ');
            card.querySelector('[data-cart-price]').textContent = formatRupiah(
                moneyToCents(item.selling_price),
            );
            card.querySelector('[data-cart-subtotal]').textContent = formatRupiah(calculateLineSubtotal(item));
            card.querySelector('[data-cart-stock]').textContent = 'Maksimal ' + formatQuantity(item.available_stock) + ' ' + item.unit_name;
            card.querySelector('[data-cart-remove]').setAttribute('aria-label', 'Hapus ' + item.name);
            card.querySelector('[data-quantity-decrease]').setAttribute('aria-label', 'Kurangi jumlah ' + item.name);
            card.querySelector('[data-quantity-increase]').setAttribute('aria-label', 'Tambah jumlah ' + item.name);
            quantityInput.value = item.quantity;
            quantityInput.max = item.available_stock;
            quantityInput.setAttribute('aria-label', 'Quantity ' + item.name);
            fragment.appendChild(card);
        });

        container.replaceChildren();

        if (items.length === 0) {
            container.appendChild(emptyState);
        } else {
            container.appendChild(fragment);
        }

        const summary = calculateCartSummary(items);
        root.querySelector('[data-cart-kind-count]').textContent = String(summary.kinds);
        root.querySelector('[data-summary-kinds]').textContent = String(summary.kinds);
        root.querySelector('[data-summary-quantity]').textContent = formatQuantity(summary.quantityMills);
        root.querySelector('[data-summary-subtotal]').textContent = formatRupiah(summary.subtotalCents);
        root.querySelector('[data-mobile-tab-count]').textContent = String(summary.kinds);
        root.querySelector('[data-mobile-cart-summary]').textContent = summary.kinds + ' item • ' + formatRupiah(summary.subtotalCents);
        clearTrigger.disabled = items.length === 0;
        mobileBar.hidden = items.length === 0;

        if (typeof options.onSummary === 'function') {
            options.onSummary(summary, items);
        }
    }

    container.addEventListener('click', function (event) {
        const card = event.target.closest('[data-product-id]');

        if (!card) {
            return;
        }

        const productId = Number(card.dataset.productId);

        if (event.target.closest('[data-cart-remove]')) {
            store.remove(productId);
        } else if (event.target.closest('[data-quantity-decrease]')) {
            store.increment(productId, -1000);
        } else if (event.target.closest('[data-quantity-increase]')) {
            store.increment(productId, 1000);
        }
    });

    container.addEventListener('change', function (event) {
        if (!event.target.matches('[data-cart-quantity]')) {
            return;
        }

        const card = event.target.closest('[data-product-id]');

        if (!store.updateQuantity(Number(card.dataset.productId), event.target.value)) {
            render(store.getItems());
        }
    });

    store.subscribe(render);

    return { render };
}
