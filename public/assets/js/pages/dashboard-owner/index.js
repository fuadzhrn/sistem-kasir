import { fetchDashboardData } from './dashboard-api.js';
import {
    filterParameters,
    initializeFilters,
    syncDashboardUrl,
} from './dashboard-filters.js';
import {
    destroyDashboardCharts,
    renderDashboardCharts,
} from './dashboard-charts.js';
import { renderDashboard } from './dashboard-renderer.js';

const root = document.querySelector('[data-owner-dashboard]');

if (root) {
    const form = root.querySelector('[data-dashboard-filter]');
    const filters = initializeFilters(form);
    const loading = root.querySelector('[data-dashboard-loading]');
    const error = root.querySelector('[data-dashboard-error]');
    let activeController = null;
    let requestSequence = 0;

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
            renderDashboardCharts(root, data.charts);

            if (updateUrl) {
                syncDashboardUrl(root.dataset.dashboardPage, parameters);
            }
        } catch (exception) {
            if (exception.name !== 'AbortError' && sequence === requestSequence) {
                showError(exception.message);
            }
        } finally {
            if (sequence === requestSequence) {
                setLoading(false);
            }
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (filters.validate()) {
            load(filters.parameters());
        }
    });

    root.querySelector('[data-dashboard-refresh]').addEventListener('click', () => {
        load(filters.parameters(), false);
    });
    root.querySelector('[data-dashboard-retry]').addEventListener('click', () => {
        load(filters.parameters(), false);
    });
    root.querySelector('[data-dashboard-reset]').addEventListener('click', (event) => {
        event.preventDefault();
        filters.reset();
        load(filterParameters(form));
    });
    window.addEventListener('beforeunload', () => {
        activeController?.abort();
        destroyDashboardCharts();
    });

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
