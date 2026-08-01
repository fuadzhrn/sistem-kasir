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
import { initializeMobileSheet } from '../../components/mobile-sheet.js';

const root = document.querySelector('[data-owner-dashboard]');

if (root) {
    const form = root.querySelector('[data-dashboard-filter]');
    const filters = initializeFilters(form);
    const filterSheet = initializeMobileSheet({
        root,
        stateController: filters,
        breakpoint: '(max-width: 768px)',
        selectors: {
            sheet: '[data-dashboard-filter-modal]',
            dialog: '[data-dashboard-filter-dialog]',
            open: '[data-dashboard-filter-open]',
            overlay: '[data-dashboard-filter-overlay]',
            close: '[data-dashboard-filter-close]',
        },
    });
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
            if (element.matches('[data-dashboard-retry]')) {
                return;
            }

            element.disabled = isLoading;
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
