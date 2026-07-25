export function initializeFilters(form) {
    const period = form.querySelector('[data-period-select]');
    const customRange = form.querySelector('[data-custom-range]');
    const dateFrom = form.elements.date_from;
    const dateTo = form.elements.date_to;

    const updateCustomRange = () => {
        const custom = period.value === 'custom';
        customRange.hidden = !custom;
        dateFrom.required = custom;
        dateTo.required = custom;
    };

    period.addEventListener('change', updateCustomRange);
    updateCustomRange();

    return {
        parameters: () => filterParameters(form),
        validate: () => validateFilter(form),
        reset: () => {
            form.reset();
            form.elements.branch_id.value = '';
            period.value = 'this_month';
            dateFrom.value = '';
            dateTo.value = '';
            updateCustomRange();
        },
    };
}

export function filterParameters(form) {
    const parameters = new URLSearchParams();

    for (const [key, value] of new FormData(form).entries()) {
        const normalized = String(value).trim();

        if (normalized !== '') {
            parameters.set(key, normalized);
        }
    }

    if (!parameters.has('period')) {
        parameters.set('period', 'this_month');
    }

    return parameters;
}

export function syncDashboardUrl(pageUrl, parameters) {
    const url = new URL(pageUrl, window.location.origin);
    url.search = parameters.toString();
    window.history.replaceState({}, '', url);
}

function validateFilter(form) {
    if (!form.reportValidity()) {
        return false;
    }

    if (form.elements.period.value !== 'custom') {
        return true;
    }

    const start = new Date(`${form.elements.date_from.value}T00:00:00`);
    const end = new Date(`${form.elements.date_to.value}T00:00:00`);
    const totalDays = Math.floor((end - start) / 86400000) + 1;

    if (!Number.isFinite(totalDays) || totalDays < 1) {
        form.elements.date_to.setCustomValidity('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        form.elements.date_to.reportValidity();
        form.elements.date_to.setCustomValidity('');
        return false;
    }

    if (totalDays > 366) {
        form.elements.date_to.setCustomValidity(
            'Rentang dashboard maksimal 366 hari. Gunakan modul laporan untuk periode yang lebih panjang.',
        );
        form.elements.date_to.reportValidity();
        form.elements.date_to.setCustomValidity('');
        return false;
    }

    return true;
}
