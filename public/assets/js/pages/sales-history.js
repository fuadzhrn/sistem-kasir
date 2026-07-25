(function () {
    'use strict';

    const root = document.querySelector('[data-sales-filters]');

    if (!root) {
        return;
    }

    const form = root.querySelector('[data-sales-filter-form]');
    const dateFrom = root.querySelector('[name="date_from"]');
    const dateTo = root.querySelector('[name="date_to"]');
    const submitButton = root.querySelector('[data-submit-sales-filters]');
    const toDateInput = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    root.querySelector('[data-reset-sales-filters]')?.addEventListener('click', function () {
        window.location.assign(form.action);
    });

    root.querySelectorAll('[data-date-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            const today = new Date();
            const from = new Date(today);
            const to = new Date(today);

            if (button.dataset.datePreset === 'yesterday') {
                from.setDate(from.getDate() - 1);
                to.setDate(to.getDate() - 1);
            } else if (button.dataset.datePreset === 'last-7-days') {
                from.setDate(from.getDate() - 6);
            } else if (button.dataset.datePreset === 'this-month') {
                from.setDate(1);
            }

            dateFrom.value = toDateInput(from);
            dateTo.value = toDateInput(to);
        });
    });

    form.addEventListener('submit', function () {
        submitButton.dataset.loading = 'true';
        submitButton.disabled = true;
    });
}());
