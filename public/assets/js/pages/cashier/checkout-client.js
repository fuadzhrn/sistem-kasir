import {
    moneyToCents,
    showToast,
} from './cashier-utils.js';

const receiptWindowName = 'receipt-print';

function centsToMoney(cents) {
    const safe = Number.isSafeInteger(cents) ? cents : 0;
    const sign = safe < 0 ? '-' : '';
    const absolute = Math.abs(safe);

    return sign + Math.floor(absolute / 100) + '.' + String(absolute % 100).padStart(2, '0');
}

function createRandomToken() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID().replaceAll('-', '');
    }

    const bytes = new Uint8Array(24);
    window.crypto.getRandomValues(bytes);

    return Array.from(bytes, function (byte) {
        return byte.toString(16).padStart(2, '0');
    }).join('');
}

export function createCheckoutClient(root, store, productBrowser, paymentForm) {
    const endpoint = root.dataset.checkoutUrl;
    const tokenStorageKey = 'cashier_checkout_token_user_'
        + root.dataset.userKey + '_branch_' + root.dataset.branchId;
    const clearButton = root.querySelector('[data-clear-cart-trigger]');
    const branchSelector = root.querySelector('[data-branch-selector]');
    let isSubmitting = false;
    let itemSignature = '';
    let checkoutToken = loadToken();

    function loadToken() {
        const stored = window.sessionStorage.getItem(tokenStorageKey) || '';

        if (/^[A-Za-z0-9_-]{16,64}$/.test(stored)) {
            return stored;
        }

        const token = createRandomToken();
        window.sessionStorage.setItem(tokenStorageKey, token);

        return token;
    }

    function rotateToken() {
        checkoutToken = createRandomToken();
        window.sessionStorage.setItem(tokenStorageKey, checkoutToken);
    }

    function setSubmitting(submitting) {
        isSubmitting = submitting;
        paymentForm.setSubmitting(submitting);

        if (clearButton) {
            clearButton.disabled = submitting || store.getItems().length === 0;
        }

        if (branchSelector) {
            branchSelector.disabled = submitting;
        }
    }

    function buildPayload(action, state) {
        return {
            checkout_token: checkoutToken,
            ...(root.dataset.canSwitchBranch === '1'
                ? { branch_id: Number(root.dataset.branchId) }
                : {}),
            items: store.getItems().map(function (item) {
                return {
                    product_id: item.product_id,
                    quantity: item.quantity,
                };
            }),
            discount_amount: centsToMoney(state.discountCents),
            payment_method_id: state.method.id,
            amount_received: state.method.type === 'cash'
                ? centsToMoney(state.receivedCents)
                : null,
            payment_action: action,
            expected_subtotal: centsToMoney(state.subtotalCents),
            expected_total: centsToMoney(state.totalCents),
            notes: null,
        };
    }

    function preOpenPrintWindow(action) {
        if (action !== 'print') {
            return null;
        }

        const printWindow = window.open('about:blank', receiptWindowName);

        if (printWindow) {
            try {
                printWindow.opener = null;
                printWindow.document.title = 'Menyiapkan struk…';
                printWindow.document.body.textContent = 'Transaksi sedang disimpan. Struk akan dibuka setelah berhasil.';
            } catch (error) {
                // Navigation still works if the browser restricts access to the blank tab.
            }
        }

        return printWindow;
    }

    function closePrintWindow(printWindow) {
        if (printWindow && !printWindow.closed) {
            printWindow.close();
        }
    }

    function safePrintUrl(value) {
        if (typeof value !== 'string' || value === '') {
            return null;
        }

        try {
            const url = new URL(value, window.location.origin);

            return url.origin === window.location.origin ? url.href : null;
        } catch (error) {
            return null;
        }
    }

    async function submit(action, state) {
        if (isSubmitting) {
            return;
        }

        const printWindow = preOpenPrintWindow(action);
        setSubmitting(true);
        paymentForm.setError('');

        try {
            const response = await window.fetch(endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(buildPayload(action, state)),
            });
            const payload = await response.json().catch(function () {
                return {
                    success: false,
                    code: 'CHECKOUT_FAILED',
                    message: 'Respons server tidak dapat dibaca.',
                };
            });

            if (!response.ok || payload.success !== true) {
                closePrintWindow(printWindow);
                handleFailure(payload);

                return;
            }

            const printUrl = safePrintUrl(payload.data?.print_url);
            const shouldOpenReceipt = action === 'print'
                && payload.data?.print_available === true
                && printUrl !== null;
            const printFallbackRequired = shouldOpenReceipt && !printWindow;

            if (shouldOpenReceipt && printWindow) {
                printWindow.location.replace(printUrl);
            } else if (printWindow) {
                closePrintWindow(printWindow);
            }

            store.clear();
            paymentForm.reset();
            rotateToken();
            await productBrowser.reload();
            paymentForm.showSuccess(payload, action, printFallbackRequired);
            showToast(
                'success',
                'Transaksi berhasil',
                payload.data.invoice_number + ' telah tersimpan.',
            );
            productBrowser.focusSearch();
        } catch (error) {
            closePrintWindow(printWindow);
            paymentForm.setError('Koneksi terputus. Coba ulangi pembayaran dengan keranjang yang sama.');
            showToast(
                'danger',
                'Checkout gagal',
                'Koneksi ke server gagal. Transaksi dapat dicoba ulang dengan aman.',
            );
        } finally {
            setSubmitting(false);
        }
    }

    function handleFailure(payload) {
        const message = payload.message || 'Transaksi belum dapat diproses.';
        paymentForm.setError(message);

        if (payload.code === 'INSUFFICIENT_STOCK' && payload.data) {
            store.applyStockConflict(payload.data);
            rotateToken();
            productBrowser.reload();
        }

        if (payload.code === 'CART_PRICE_CHANGED' && Array.isArray(payload.data?.items)) {
            store.updatePrices(payload.data.items);
            rotateToken();
            productBrowser.reload();
        }

        showToast(
            payload.code === 'CART_PRICE_CHANGED' ? 'warning' : 'danger',
            payload.code === 'CART_PRICE_CHANGED' ? 'Harga berubah' : 'Transaksi ditolak',
            message,
        );
    }

    store.subscribe(function (items) {
        const nextSignature = items
            .map(function (item) {
                return [
                    item.product_id,
                    item.quantity,
                    moneyToCents(item.selling_price),
                ].join(':');
            })
            .sort()
            .join('|');

        if (itemSignature !== '' && itemSignature !== nextSignature && !isSubmitting) {
            rotateToken();
        }

        itemSignature = nextSignature;
    });

    return {
        isSubmitting: function () {
            return isSubmitting;
        },
        submit,
    };
}
