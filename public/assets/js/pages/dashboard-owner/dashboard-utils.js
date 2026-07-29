export function formatRupiah(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return 'Rp0';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
        .format(number)
        .replace(/\s/g, '')
        .replace('Rp-', '-Rp');
}

export function formatQuantity(value) {
    return window.StoreApp && window.StoreApp.quantity
        ? window.StoreApp.quantity.format(value)
        : '0';
}

export function createCell(content, className = '', label = '') {
    const cell = document.createElement('td');
    cell.textContent = content ?? '';

    if (className) {
        cell.className = className;
    }

    if (label) {
        cell.dataset.label = label;
    }

    return cell;
}

export function createLinkCell(label, href, secondary = '', fieldLabel = '') {
    const cell = document.createElement('td');
    const link = document.createElement('a');
    const strong = document.createElement('strong');
    strong.textContent = label ?? '';
    link.append(strong);

    if (safeInternalUrl(href)) {
        link.href = href;
    }

    cell.append(link);

    if (secondary) {
        const small = document.createElement('small');
        small.textContent = secondary;
        cell.append(small);
    }

    if (fieldLabel) {
        cell.dataset.label = fieldLabel;
    }

    return cell;
}

export function createBadgeCell(label, variant = 'neutral', fieldLabel = '') {
    const cell = document.createElement('td');
    const badge = document.createElement('span');
    badge.className = `badge badge-${safeVariant(variant)}`;
    badge.textContent = label ?? '';
    cell.append(badge);

    if (fieldLabel) {
        cell.dataset.label = fieldLabel;
    }

    return cell;
}

export function appendMobileDetail(cell, href) {
    if (!safeInternalUrl(href)) {
        return;
    }

    const link = document.createElement('a');
    link.className = 'dashboard-mobile-detail';
    link.href = href;
    link.textContent = 'Lihat Detail';
    cell.append(link);
}

export function emptyRow(columnCount, message) {
    const row = document.createElement('tr');
    const cell = createCell(message, 'table-empty');
    cell.colSpan = columnCount;
    row.append(cell);

    return row;
}

function safeVariant(variant) {
    return ['success', 'warning', 'danger', 'info', 'neutral'].includes(variant)
        ? variant
        : 'neutral';
}

function safeInternalUrl(value) {
    if (!value) {
        return false;
    }

    try {
        return new URL(value, window.location.origin).origin === window.location.origin;
    } catch {
        return false;
    }
}
