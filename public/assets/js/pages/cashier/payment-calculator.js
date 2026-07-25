import {
    moneyToCents,
    multiplyPriceByQuantity,
    quantityToMills,
} from './cashier-utils.js';

export function calculateLineSubtotal(item) {
    const priceCents = moneyToCents(item.selling_price);
    const quantityMills = quantityToMills(item.quantity);

    if (priceCents === null || quantityMills === null) {
        return 0;
    }

    return multiplyPriceByQuantity(priceCents, quantityMills);
}

export function calculateCartSummary(items, discountCents = 0) {
    const subtotalCents = items.reduce(function (total, item) {
        return total + calculateLineSubtotal(item);
    }, 0);
    const safeDiscount = Number.isSafeInteger(discountCents)
        ? Math.max(0, discountCents)
        : 0;

    return {
        kinds: items.length,
        quantityMills: items.reduce(function (total, item) {
            return total + (quantityToMills(item.quantity) || 0);
        }, 0),
        subtotalCents,
        discountCents: safeDiscount,
        totalCents: Math.max(0, subtotalCents - safeDiscount),
    };
}
