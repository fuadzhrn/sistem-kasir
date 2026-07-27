export function debounce(callback, delay) {
    let timeoutId;

    return function debounced(...args) {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(function () {
            callback(...args);
        }, delay);
    };
}

export function toScaledInteger(value, scale) {
    const normalized = String(value ?? '').trim().replace(',', '.');
    const pattern = new RegExp('^\\d+(?:\\.\\d{0,' + scale + '})?$');

    if (!pattern.test(normalized)) {
        return null;
    }

    const [whole, fraction = ''] = normalized.split('.');
    const factor = 10 ** scale;
    const result = (Number.parseInt(whole, 10) * factor)
        + Number.parseInt(fraction.padEnd(scale, '0') || '0', 10);

    return Number.isSafeInteger(result) ? result : null;
}

export function quantityToMills(value) {
    return window.StoreApp && window.StoreApp.quantity
        ? window.StoreApp.quantity.toMills(value)
        : null;
}

export function moneyToCents(value) {
    return toScaledInteger(value, 2);
}

export function rupiahInputToCents(value) {
    const normalized = String(value ?? '').trim();
    let digits = normalized;

    if (/^\d{1,3}(?:\.\d{3})+$/.test(normalized)) {
        digits = normalized.replaceAll('.', '');
    } else if (!/^\d+$/.test(normalized)) {
        return null;
    }

    const cents = Number.parseInt(digits, 10) * 100;

    return Number.isSafeInteger(cents) ? cents : null;
}

export function formatRupiahInput(value) {
    const normalized = String(value ?? '').trim();

    if (normalized === '') {
        return '';
    }

    if (!/^\d[\d.]*$/.test(normalized)) {
        return normalized;
    }

    const rupiah = Number.parseInt(normalized.replaceAll('.', ''), 10);
    const cents = rupiah * 100;

    return Number.isSafeInteger(cents) ? formatRupiah(cents).slice(2) : normalized;
}

export function millsToQuantity(value) {
    return window.StoreApp && window.StoreApp.quantity
        ? window.StoreApp.quantity.fromMills(value) || '0'
        : '0';
}

export function formatQuantity(value) {
    if (!window.StoreApp || !window.StoreApp.quantity) {
        return '0';
    }

    return typeof value === 'number'
        ? window.StoreApp.quantity.formatMills(value)
        : window.StoreApp.quantity.format(value);
}

export function formatRupiah(cents) {
    const safeCents = Number.isSafeInteger(cents) ? Math.max(0, cents) : 0;
    const rupiah = safeCents / 100;
    const result = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(rupiah);

    return 'Rp' + result;
}

export function multiplyPriceByQuantity(priceCents, quantityMills) {
    if (!Number.isSafeInteger(priceCents) || !Number.isSafeInteger(quantityMills)) {
        return 0;
    }

    const value = priceCents * quantityMills;

    if (!Number.isSafeInteger(value)) {
        return 0;
    }

    return Math.floor((value + 500) / 1000);
}

export function showToast(type, title, message) {
    if (window.StoreApp && typeof window.StoreApp.showToast === 'function') {
        window.StoreApp.showToast({ type, title, message });
    }
}
