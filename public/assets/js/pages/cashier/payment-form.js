import { calculateCartSummary } from './payment-calculator.js';
import {
    formatRupiah,
    moneyToCents,
    rupiahInputToCents,
} from './cashier-utils.js';

export function createPaymentForm(root, store) {
    const discountInput = root.querySelector('[data-payment-discount]');
    const methodSelect = root.querySelector('[data-payment-method]');
    const receivedInput = root.querySelector('[data-amount-received]');
    const cashGroup = root.querySelector('[data-cash-received-group]');
    const noncashNotice = root.querySelector('[data-noncash-notice]');
    const errorOutput = root.querySelector('[data-payment-error]');
    const actionButtons = Array.from(root.querySelectorAll('[data-payment-action]'));
    const maximumDiscountCents = moneyToCents(root.dataset.maximumDiscount) || 0;
    let items = [];
    let state = calculateCartSummary([]);

    function selectedMethod() {
        if (!methodSelect || methodSelect.selectedIndex < 0) {
            return null;
        }

        const option = methodSelect.options[methodSelect.selectedIndex];

        return {
            id: option.value,
            code: option.dataset.code,
            type: option.dataset.type,
            name: option.textContent.trim(),
        };
    }

    function calculate() {
        const method = selectedMethod();
        const discountCents = rupiahInputToCents(discountInput.value);
        state = calculateCartSummary(items, discountCents || 0);
        const isCash = method && method.type === 'cash';
        const receivedCents = isCash ? rupiahInputToCents(receivedInput.value) : state.totalCents;
        const errors = [];

        if (items.length === 0) {
            errors.push('Keranjang masih kosong.');
        }

        if (!method) {
            errors.push('Metode pembayaran belum tersedia.');
        }

        if (discountCents === null || discountCents < 0) {
            errors.push('Diskon tidak valid.');
        } else if (discountCents > state.subtotalCents) {
            errors.push('Diskon tidak boleh melebihi subtotal.');
        } else if (discountCents > maximumDiscountCents) {
            errors.push('Diskon melebihi batas preview yang tersedia.');
        }

        if (isCash && (receivedCents === null || receivedCents < state.totalCents)) {
            errors.push('Uang diterima harus mencukupi total pembayaran.');
        }

        const changeCents = isCash && receivedCents !== null
            ? Math.max(0, receivedCents - state.totalCents)
            : 0;
        root.querySelector('[data-summary-discount]').textContent = formatRupiah(state.discountCents);
        root.querySelector('[data-summary-total]').textContent = formatRupiah(state.totalCents);
        root.querySelector('[data-payment-change]').textContent = formatRupiah(changeCents);
        root.querySelector('[data-mobile-cart-summary]').textContent = state.kinds + ' item • ' + formatRupiah(state.totalCents);
        errorOutput.textContent = errors[0] || '';
        actionButtons.forEach(function (button) {
            button.disabled = errors.length > 0 || !root.dataset.branchId;
        });

        return {
            ...state,
            method,
            receivedCents: receivedCents || 0,
            changeCents,
            valid: errors.length === 0 && Boolean(root.dataset.branchId),
        };
    }

    function updateMethod() {
        const method = selectedMethod();
        const isCash = method && method.type === 'cash';
        cashGroup.hidden = !isCash;
        receivedInput.disabled = !isCash;
        noncashNotice.hidden = isCash || !method;

        if (!isCash) {
            receivedInput.value = '';
        }

        calculate();
    }

    function showPreview(action) {
        const preview = calculate();

        if (!preview.valid) {
            return;
        }

        root.querySelector('[data-preview-branch]').textContent = root.dataset.branchName || '-';
        root.querySelector('[data-preview-items]').textContent = preview.kinds + ' jenis produk';
        root.querySelector('[data-preview-subtotal]').textContent = formatRupiah(preview.subtotalCents);
        root.querySelector('[data-preview-discount]').textContent = formatRupiah(preview.discountCents);
        root.querySelector('[data-preview-total]').textContent = formatRupiah(preview.totalCents);
        root.querySelector('[data-preview-method]').textContent = preview.method.name;
        root.querySelector('[data-preview-received]').textContent = formatRupiah(preview.receivedCents);
        root.querySelector('[data-preview-change]').textContent = formatRupiah(preview.changeCents);
        root.querySelector('[data-preview-cash-row]').hidden = preview.method.type !== 'cash';
        root.querySelector('[data-preview-message]').textContent = action === 'print'
            ? 'Simulasi berhasil. Transaksi belum disimpan dan stok belum dikurangi. Penyimpanan transaksi akan dibuat pada Tahap 13 dan cetak struk akan diaktifkan pada tahap cetak struk.'
            : 'Simulasi berhasil. Transaksi belum disimpan dan stok belum dikurangi. Penyimpanan transaksi akan dibuat pada Tahap 13.';
        window.StoreApp.modal.open('cashier-payment-preview-modal');
    }

    discountInput.addEventListener('input', calculate);
    receivedInput.addEventListener('input', calculate);
    methodSelect?.addEventListener('change', updateMethod);
    actionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            showPreview(button.dataset.paymentAction);
        });
    });
    store.subscribe(function (cartItems) {
        items = cartItems;
        calculate();
    });
    updateMethod();

    return {
        recalculate: calculate,
        reset: function () {
            discountInput.value = '0';
            receivedInput.value = '';
            calculate();
        },
    };
}
