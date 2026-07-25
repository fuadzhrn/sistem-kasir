import { initializeBranchSwitcher } from './branch-switcher.js';
import { createCartRenderer } from './cart-renderer.js';
import { createCartStore } from './cart-store.js';
import { initializeMobileTabs } from './mobile-tabs.js';
import { createPaymentForm } from './payment-form.js';
import { createProductBrowser } from './product-browser.js';
import { showToast } from './cashier-utils.js';

document.addEventListener('DOMContentLoaded', async function () {
    const root = document.querySelector('[data-cashier-root]');

    if (!root) {
        return;
    }

    const store = createCartStore({
        branchId: root.dataset.branchId,
        userKey: root.dataset.userKey,
    });
    const paymentForm = createPaymentForm(root, store);
    createCartRenderer(root, store, {
        onSummary: function () {
            paymentForm.recalculate();
        },
    });
    initializeMobileTabs(root);
    initializeBranchSwitcher(root, store);

    const productBrowser = createProductBrowser(root, store);

    if (root.dataset.branchId) {
        await productBrowser.load();
        await store.revalidate(productBrowser.lookupStoredProduct);
    }

    root.querySelector('[data-confirm-clear-cart]').addEventListener('click', function () {
        store.clear();
        paymentForm.reset();
        window.StoreApp.modal.close('cashier-clear-cart-modal', 'confirmed');
        showToast('success', 'Keranjang dikosongkan', 'Seluruh item sementara telah dihapus.');
    });
});
