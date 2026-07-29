import { fetchDashboardData } from './dashboard-api.js';
import {
    filterParameters,
    initializeFilters,
    syncDashboardUrl,
} from './dashboard-filters.js';
import {
    destroyDashboardCharts,
    renderDashboardCharts,
    resizeDashboardCharts,
} from './dashboard-charts.js';
import { renderDashboard } from './dashboard-renderer.js';

const root = document.querySelector('[data-admin-dashboard]');

if (root) {
    const form = root.querySelector('[data-dashboard-filter]');
    const filters = initializeFilters(form);
    const filterSheet = initializeFilterSheet(root, filters);
    const loading = root.querySelector('[data-dashboard-loading]');
    const error = root.querySelector('[data-dashboard-error]');
    let activeController = null;
    let requestSequence = 0;
    let latestChartData = null;
    const chartBreakpoint = window.matchMedia('(max-width: 768px)');

    const load = async (parameters, updateUrl = true) => {
        activeController?.abort();
        activeController = new AbortController();
        const sequence = ++requestSequence;
        setLoading(true);
        hideError();

        try {
            const data = await fetchDashboardData(
                root.dataset.dashboardEndpoint,
                parameters,
                activeController.signal,
            );

            if (sequence !== requestSequence) {
                return;
            }

            renderDashboard(root, data);
            latestChartData = data.charts;
            renderDashboardCharts(root, data.charts);

            if (updateUrl) {
                syncDashboardUrl(root.dataset.dashboardPage, parameters);
            }

            return true;
        } catch (exception) {
            if (exception.name !== 'AbortError' && sequence === requestSequence) {
                showError(exception.message);
            }

            return false;
        } finally {
            if (sequence === requestSequence) {
                setLoading(false);
            }
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (filters.validate()) {
            const parameters = filters.parameters();
            filterSheet.close('apply');

            if (await load(parameters)) {
                filters.commit();
            }
        }
    });
    root.querySelector('[data-dashboard-refresh]').addEventListener('click', () => {
        load(filters.parameters(), false);
    });
    root.querySelector('[data-dashboard-retry]').addEventListener('click', () => {
        load(filters.parameters(), false);
    });
    root.querySelector('[data-dashboard-reset]').addEventListener('click', async (event) => {
        event.preventDefault();
        filters.reset();
        const parameters = filterParameters(form);
        filterSheet.close('reset');

        if (await load(parameters)) {
            filters.commit();
        }
    });
    window.addEventListener('beforeunload', () => {
        activeController?.abort();
        destroyDashboardCharts();
    });
    document.addEventListener('store-app:sidebar-changed', () => {
        window.requestAnimationFrame(() => resizeDashboardCharts());
    });
    const handleChartBreakpoint = () => {
        if (latestChartData) {
            renderDashboardCharts(root, latestChartData);
        }
    };

    if (typeof chartBreakpoint.addEventListener === 'function') {
        chartBreakpoint.addEventListener('change', handleChartBreakpoint);
    } else {
        chartBreakpoint.addListener(handleChartBreakpoint);
    }

    load(filters.parameters(), false);

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', isLoading);
        loading.hidden = !isLoading;
        root.querySelectorAll('button, select, input').forEach((element) => {
            if (!element.matches('[data-dashboard-retry]')) {
                element.disabled = isLoading;
            }
        });
    }

    function showError(message) {
        error.hidden = false;
        error.querySelector('[data-dashboard-error-message]').textContent = message;
    }

    function hideError() {
        error.hidden = true;
    }
}

function initializeFilterSheet(root, filters) {
    const sheet = root.querySelector('[data-admin-filter-modal]');
    const dialog = root.querySelector('[data-admin-filter-dialog]');
    const openButton = root.querySelector('[data-admin-filter-open]');
    const overlay = root.querySelector('[data-admin-filter-overlay]');
    const closeButtons = Array.from(root.querySelectorAll('[data-admin-filter-close]'));
    const compactLayout = window.matchMedia('(max-width: 1024px)');
    let isOpen = false;

    if (!sheet || !dialog || !openButton || !overlay) {
        return { close: () => false };
    }

    const focusableSelector = [
        'a[href]',
        'button:not(:disabled)',
        'input:not(:disabled)',
        'select:not(:disabled)',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const syncMode = () => {
        if (compactLayout.matches) {
            sheet.setAttribute('aria-hidden', String(!isOpen));
            dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');
            return;
        }

        close('viewport');
        sheet.removeAttribute('aria-hidden');
        dialog.removeAttribute('role');
        dialog.removeAttribute('aria-modal');
    };

    const open = () => {
        if (!compactLayout.matches || isOpen) {
            return false;
        }

        filters.restore();
        isOpen = true;
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        openButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('is-modal-open');
        window.requestAnimationFrame(() => {
            (dialog.querySelector(focusableSelector) || dialog).focus();
        });

        return true;
    };

    function close(reason = 'close') {
        if (!isOpen) {
            return false;
        }

        isOpen = false;
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        openButton.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('is-modal-open');

        if (!['apply', 'reset'].includes(reason)) {
            filters.restore();
        }

        if (reason !== 'viewport') {
            openButton.focus();
        }

        return true;
    }

    const trapFocus = (event) => {
        if (!isOpen || event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(dialog.querySelectorAll(focusableSelector))
            .filter((element) => !element.hidden);

        if (focusable.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    openButton.addEventListener('click', open);
    overlay.addEventListener('click', () => close('overlay'));
    closeButtons.forEach((button) => {
        button.addEventListener('click', () => close('cancel'));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            event.preventDefault();
            close('escape');
            return;
        }

        trapFocus(event);
    });

    if (typeof compactLayout.addEventListener === 'function') {
        compactLayout.addEventListener('change', syncMode);
    } else {
        compactLayout.addListener(syncMode);
    }

    syncMode();

    return { close };
}
