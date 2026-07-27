(function (window) {
    'use strict';

    window.StoreApp = window.StoreApp || {};

    function normalizedDecimal(value) {
        const raw = String(value ?? '').trim();

        if (raw === '') {
            return null;
        }

        if (raw.includes(',')) {
            if (!/^-?(?:\d+|\d{1,3}(?:\.\d{3})+),\d{1,3}$/.test(raw)) {
                return null;
            }

            return raw.replaceAll('.', '').replace(',', '.');
        }

        return /^-?\d+(?:\.\d{1,3})?$/.test(raw) ? raw : null;
    }

    function toMills(value) {
        const normalized = normalizedDecimal(value);

        if (normalized === null) {
            return null;
        }

        const negative = normalized.startsWith('-');
        const unsigned = normalized.replace(/^-/, '');
        const [whole, fraction = ''] = unsigned.split('.');
        const mills = (Number.parseInt(whole, 10) * 1000)
            + Number.parseInt(fraction.padEnd(3, '0') || '0', 10);
        const signed = negative ? -mills : mills;

        return Number.isSafeInteger(signed) ? signed : null;
    }

    function fromMills(value) {
        if (!Number.isSafeInteger(value)) {
            return null;
        }

        const negative = value < 0;
        const absolute = Math.abs(value);
        const whole = Math.floor(absolute / 1000);
        const fraction = String(absolute % 1000).padStart(3, '0').replace(/0+$/, '');
        const result = fraction === '' ? String(whole) : whole + '.' + fraction;

        return negative && absolute !== 0 ? '-' + result : result;
    }

    function formatMills(value) {
        const normalized = fromMills(value);

        if (normalized === null) {
            return '0';
        }

        const negative = normalized.startsWith('-');
        const unsigned = normalized.replace(/^-/, '');
        const [whole, fraction = ''] = unsigned.split('.');
        const groupedWhole = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
            useGrouping: true,
        }).format(Number.parseInt(whole, 10));
        const result = fraction === '' ? groupedWhole : groupedWhole + ',' + fraction;

        return negative && result !== '0' ? '-' + result : result;
    }

    function format(value) {
        const mills = toMills(value);

        return mills === null ? '0' : formatMills(mills);
    }

    function normalizeInput(value) {
        const mills = toMills(value);

        return mills === null ? null : fromMills(mills);
    }

    function inputValue(value) {
        const normalized = normalizeInput(value);

        return normalized === null ? null : normalized.replace('.', ',');
    }

    window.StoreApp.quantity = Object.freeze({
        format: format,
        formatMills: formatMills,
        fromMills: fromMills,
        inputValue: inputValue,
        normalizeInput: normalizeInput,
        toMills: toMills,
    });

    function validateInput(input) {
        const value = input.value.trim();
        const mills = value === '' ? null : toMills(value);
        const invalid = value !== '' && (mills === null || mills < 0);

        input.setCustomValidity(
            invalid ? 'Gunakan quantity positif dengan maksimal tiga angka desimal.' : '',
        );

        return !invalid;
    }

    window.document.addEventListener('input', function (event) {
        if (event.target.matches('[data-quantity-input]')) {
            validateInput(event.target);
        }
    });

    window.document.addEventListener('blur', function (event) {
        if (!event.target.matches('[data-quantity-input]') || !validateInput(event.target)) {
            return;
        }

        if (event.target.value.trim() !== '') {
            event.target.value = inputValue(event.target.value);
        }
    }, true);
})(window);
