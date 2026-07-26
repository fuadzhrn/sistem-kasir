const root = document.querySelector('[data-cashier-dashboard]');

if (root) {
    const form = root.querySelector('[data-cashier-dashboard-filter]');
    const submit = form?.querySelector('[data-filter-submit]');

    form?.addEventListener('submit', () => {
        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Memuat…';
        }
    });

    if (!new URLSearchParams(window.location.search).toString()) {
        root.querySelector('[data-cashier-dashboard-search]')?.focus();
    }
}
