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
    const toggleButton = document.querySelector('[data-sales-filter-toggle]');
    const closeButton = root.querySelector('[data-sales-filter-close]');
    const mobileMedia = window.matchMedia('(max-width: 768px)');
    const toDateInput = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    root.querySelector('[data-reset-sales-filters]')?.addEventListener('click', function () {
        window.location.assign(form.action);
    });

    function setFilterOpen(open, restoreFocus = true) {
        const shouldOpen = Boolean(open);
        root.classList.toggle('is-open', shouldOpen);
        toggleButton?.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

        if (shouldOpen) {
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.requestAnimationFrame(function () {
                root.querySelector('input:not([readonly]), select:not([disabled])')?.focus();
            });
        } else if (restoreFocus) {
            toggleButton?.focus();
        }
    }

    root.classList.add('is-mobile-ready');
    toggleButton?.addEventListener('click', function () {
        setFilterOpen(!root.classList.contains('is-open'));
    });
    closeButton?.addEventListener('click', function () {
        setFilterOpen(false);
    });
    document.addEventListener('keydown', function (event) {
        if (
            event.key === 'Escape'
            && mobileMedia.matches
            && root.classList.contains('is-open')
        ) {
            event.preventDefault();
            setFilterOpen(false);
        }
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
        submitButton.textContent = 'Menerapkan...';
    });
}());
