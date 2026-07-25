import {
    millsToQuantity,
    moneyToCents,
    quantityToMills,
    showToast,
} from './cashier-utils.js';

export function createCartStore(options) {
    const items = new Map();
    const subscribers = new Set();
    const branchId = String(options.branchId || '');
    const storageKey = branchId === ''
        ? null
        : 'cashier_cart_user_' + String(options.userKey || '0') + '_branch_' + branchId;

    function notify() {
        save();
        const snapshot = getItems();
        subscribers.forEach(function (subscriber) {
            subscriber(snapshot);
        });
    }

    function save() {
        if (!storageKey) {
            return;
        }

        const safeItems = getItems().map(function (item) {
            return {
                product_id: item.product_id,
                code: item.code,
                quantity: item.quantity,
                selling_price: item.selling_price,
                available_stock: item.available_stock,
            };
        });

        window.sessionStorage.setItem(storageKey, JSON.stringify({
            branch_id: branchId,
            items: safeItems,
        }));
    }

    function loadStored() {
        if (!storageKey) {
            return [];
        }

        try {
            const stored = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');

            if (String(stored.branch_id) !== branchId || !Array.isArray(stored.items)) {
                return [];
            }

            return stored.items.filter(function (item) {
                return Number.isInteger(item.product_id)
                    && typeof item.code === 'string'
                    && quantityToMills(item.quantity) > 0;
            });
        } catch (error) {
            window.sessionStorage.removeItem(storageKey);

            return [];
        }
    }

    async function revalidate(loader) {
        const storedItems = loadStored();
        let changed = false;

        for (const stored of storedItems) {
            const product = await loader(stored);

            if (!product || !product.is_available) {
                changed = true;
                continue;
            }

            const requested = quantityToMills(stored.quantity) || 0;
            const available = quantityToMills(product.stock_quantity) || 0;
            const adjusted = Math.min(requested, available);

            if (adjusted <= 0) {
                changed = true;
                continue;
            }

            if (
                adjusted !== requested
                || moneyToCents(stored.selling_price) !== moneyToCents(product.selling_price)
                || quantityToMills(stored.available_stock) !== available
            ) {
                changed = true;
            }

            items.set(product.id, normalizeItem(product, adjusted));
        }

        notify();

        if (changed) {
            showToast(
                'warning',
                'Keranjang diperbarui',
                'Harga, ketersediaan, atau stok produk disesuaikan dengan data terbaru.',
            );
        }
    }

    function normalizeItem(product, quantityMills) {
        return {
            product_id: Number(product.id),
            code: String(product.code),
            name: String(product.name),
            size: product.size || '',
            unit_name: product.unit_symbol || product.unit_name || '',
            selling_price: String(product.selling_price),
            quantity: millsToQuantity(quantityMills),
            available_stock: String(product.stock_quantity),
            image_url: String(product.image_url),
            branch_id: branchId,
        };
    }

    function add(product) {
        const available = quantityToMills(product.stock_quantity) || 0;
        const current = items.get(product.id);
        const nextQuantity = (current ? quantityToMills(current.quantity) : 0) + 1000;

        if (available <= 0 || nextQuantity > available) {
            showToast('warning', 'Stok tidak mencukupi', 'Quantity preview tidak boleh melebihi stok cabang.');

            return false;
        }

        items.set(product.id, normalizeItem(product, nextQuantity));
        notify();

        return true;
    }

    function updateQuantity(productId, value) {
        const item = items.get(Number(productId));
        const quantity = quantityToMills(value);

        if (!item || quantity === null || quantity < 0) {
            showToast('danger', 'Quantity tidak valid', 'Gunakan quantity positif dengan maksimal tiga desimal.');

            return false;
        }

        if (quantity === 0) {
            return remove(productId);
        }

        const available = quantityToMills(item.available_stock) || 0;

        if (quantity > available) {
            showToast('warning', 'Melebihi stok', 'Quantity disesuaikan maksimal sebanyak stok yang tersedia.');

            return false;
        }

        item.quantity = millsToQuantity(quantity);
        items.set(Number(productId), item);
        notify();

        return true;
    }

    function increment(productId, amountMills) {
        const item = items.get(Number(productId));

        if (!item) {
            return false;
        }

        const current = quantityToMills(item.quantity) || 0;
        const next = Math.max(0, current + amountMills);

        return updateQuantity(productId, millsToQuantity(next));
    }

    function remove(productId) {
        const removed = items.delete(Number(productId));

        if (removed) {
            notify();
        }

        return removed;
    }

    function clear() {
        items.clear();

        if (storageKey) {
            window.sessionStorage.removeItem(storageKey);
        }

        notify();
    }

    function getItems() {
        return Array.from(items.values()).map(function (item) {
            return { ...item };
        });
    }

    function subscribe(callback) {
        subscribers.add(callback);
        callback(getItems());

        return function unsubscribe() {
            subscribers.delete(callback);
        };
    }

    return {
        add,
        clear,
        getItems,
        increment,
        revalidate,
        remove,
        subscribe,
        updateQuantity,
    };
}
