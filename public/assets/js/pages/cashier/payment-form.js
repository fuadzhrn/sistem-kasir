import { calculateCartSummary } from './payment-calculator.js';
import {
    formatRupiah,
    moneyToCents,
    rupiahInputToCents,
} from './cashier-utils.js';

export function createPaymentForm(root, store, options = {}) {
    const discountInput = root.querySelector('[data-payment-discount]');
    const methodSelect = root.querySelector('[data-payment-method]');
    const receivedInput = root.querySelector('[data-amount-received]');
    const cashGroup = root.querySelector('[data-cash-received-group]');
    const noncashNotice = root.querySelector('[data-noncash-notice]');
    const errorOutput = root.querySelector('[data-payment-error]');
    const actionButtons = Array.from(root.querySelectorAll('[data-payment-action]'));
    const maximumDiscountCents = moneyToCents(root.dataset.maximumDiscount) || 0;
    const discountRestricted = root.dataset.discountRestricted === '1';
    let items = [];
    let state = calculateCartSummary([]);
    let isSubmitting = false;

    function selectedMethod() {
        if (!methodSelect || methodSelect.selectedIndex < 0) {
            return null;
        }

        const option = methodSelect.options[methodSelect.selectedIndex];

        return {
            id: Number(option.value),
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
        } else if (discountRestricted && discountCents > maximumDiscountCents) {
            errors.push('Diskon melebihi batas akun Anda.');
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
            button.disabled = isSubmitting || errors.length > 0 || !root.dataset.branchId;
        });

        return {
            ...state,
            method,
            receivedCents: receivedCents || 0,
            changeCents,
            valid: !isSubmitting && errors.length === 0 && Boolean(root.dataset.branchId),
        };
    }

    function updateMethod() {
        const method = selectedMethod();
        const isCash = method && method.type === 'cash';
        cashGroup.hidden = !isCash;
        receivedInput.disabled = !isCash || isSubmitting;
        noncashNotice.hidden = isCash || !method;

        if (!isCash) {
            receivedInput.value = '';
        }

        calculate();
    }

    function setSubmitting(submitting) {
        isSubmitting = submitting;
        discountInput.disabled = submitting;
        methodSelect.disabled = submitting;
        receivedInput.disabled = submitting || selectedMethod()?.type !== 'cash';
        actionButtons.forEach(function (button) {
            if (!button.dataset.defaultLabel) {
                button.dataset.defaultLabel = button.textContent;
            }

            button.textContent = submitting ? 'Memproses…' : button.dataset.defaultLabel;
        });
        calculate();
    }

    function showSuccess(payload, action) {
        const data = payload.data;
        root.querySelector('[data-preview-invoice]').textContent = data.invoice_number;
        root.querySelector('[data-preview-branch]').textContent = data.branch_name;
        root.querySelector('[data-preview-items]').textContent = data.item_count + ' jenis produk';
        root.querySelector('[data-preview-subtotal]').textContent = formatRupiah(moneyToCents(data.subtotal));
        root.querySelector('[data-preview-discount]').textContent = formatRupiah(moneyToCents(data.discount_amount));
        root.querySelector('[data-preview-total]').textContent = formatRupiah(moneyToCents(data.total));
        root.querySelector('[data-preview-method]').textContent = data.payment_method.name;
        root.querySelector('[data-preview-received]').textContent = formatRupiah(moneyToCents(data.amount_paid));
        root.querySelector('[data-preview-change]').textContent = formatRupiah(moneyToCents(data.change_amount));
        root.querySelector('[data-preview-cash-row]').hidden = data.payment_method.type !== 'cash';
        root.querySelector('[data-preview-message]').textContent = action === 'print'
            ? 'Transaksi berhasil disimpan. Fitur cetak struk akan diaktifkan pada tahap berikutnya.'
            : 'Transaksi berhasil disimpan tanpa mencetak struk.';
        window.StoreApp.modal.open('cashier-payment-preview-modal');
    }

    discountInput.addEventListener('input', calculate);
    receivedInput.addEventListener('input', calculate);
    methodSelect?.addEventListener('change', updateMethod);
    actionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const paymentState = calculate();

            if (paymentState.valid && typeof options.onSubmit === 'function') {
                options.onSubmit(button.dataset.paymentAction, paymentState);
            }
        });
    });
    store.subscribe(function (cartItems) {
        items = cartItems;
        calculate();
    });
    updateMethod();

    return {
        getState: calculate,
        recalculate: calculate,
        reset: function () {
            discountInput.value = '0';
            receivedInput.value = '';
            calculate();
        },
        setError: function (message) {
            errorOutput.textContent = message || '';
        },
        setSubmitting,
        showSuccess,
    };
}
