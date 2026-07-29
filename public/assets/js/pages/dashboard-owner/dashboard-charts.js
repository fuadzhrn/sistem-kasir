import { formatRupiah } from './dashboard-utils.js';

const instances = new Map();

export function renderDashboardCharts(root, charts) {
    renderLineChart(root, 'sales_trend', charts.sales_trend, [
        dataset('Omzet', charts.sales_trend.gross_sales, '--color-info'),
        dataset('Penjualan Bersih', charts.sales_trend.net_sales, '--color-primary'),
    ]);
    renderLineChart(root, 'profit_trend', charts.profit_trend, [
        dataset('Laba Kotor', charts.profit_trend.gross_profit, '--color-warning'),
        dataset('Laba Bersih', charts.profit_trend.net_profit, '--color-primary-dark'),
    ]);
    renderBarChart(root, charts.branch_comparison);
    renderDoughnutChart(root, charts.payment_composition);
}

export function destroyDashboardCharts() {
    instances.forEach((chart) => chart.destroy());
    instances.clear();
}

export function resizeDashboardCharts() {
    instances.forEach((chart) => chart.resize());
}

function renderLineChart(root, key, data, datasets) {
    if (toggleEmpty(root, key, data.empty)) {
        destroy(key);
        return;
    }

    create(key, root, {
        type: 'line',
        data: { labels: data.labels, datasets },
        options: commonOptions(),
    });
}

function renderBarChart(root, data) {
    const key = 'branch_comparison';

    if (toggleEmpty(root, key, data.empty)) {
        destroy(key);
        return;
    }

    create(key, root, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                dataset('Penjualan Bersih', data.net_sales, '--color-info', false),
                dataset('Laba Bersih', data.net_profit, '--color-primary-dark', false),
            ],
        },
        options: branchComparisonOptions(),
    });

    const subtitle = root.querySelector('[data-branch-chart-subtitle]');
    subtitle.textContent = data.grouped_others
        ? '11 cabang terbesar; cabang lain digabung sebagai Cabang Lainnya.'
        : 'Penjualan bersih dan laba bersih menurut cabang.';
}

function renderDoughnutChart(root, data) {
    const key = 'payment_composition';

    if (toggleEmpty(root, key, data.empty)) {
        destroy(key);
        return;
    }

    create(key, root, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Penjualan Bersih',
                data: data.values,
                backgroundColor: [
                    '#166534',
                    '#2563eb',
                    '#f59e0b',
                    '#7c3aed',
                    '#0891b2',
                    '#be123c',
                    '#4d7c0f',
                    '#475569',
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6,
            }],
        },
        options: {
            ...commonOptions(false),
            plugins: {
                ...commonOptions(false).plugins,
                tooltip: {
                    callbacks: {
                        label(context) {
                            const percentage = data.percentages[context.dataIndex] ?? 0;
                            return `${context.label}: ${formatRupiah(context.raw)} (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });
}

function dataset(label, data, colorVariable, fill = true) {
    const color = cssColor(colorVariable);
    const mobile = isMobileViewport();

    return {
        label,
        data,
        borderColor: color,
        backgroundColor: fill ? withAlpha(color, 0.12) : color,
        pointBackgroundColor: color,
        borderWidth: 2,
        pointHitRadius: 14,
        pointRadius: mobile ? 3 : 2,
        pointHoverRadius: mobile ? 5 : 4,
        fill,
        tension: 0.25,
    };
}

function commonOptions(showScales = true) {
    const mobile = isMobileViewport();

    return {
        responsive: true,
        maintainAspectRatio: false,
        resizeDelay: 100,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        animation: {
            duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 250,
        },
        scales: showScales ? {
            x: {
                ticks: {
                    autoSkip: true,
                    maxRotation: 0,
                    maxTicksLimit: mobile ? 6 : 12,
                },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: (value) => formatRupiah(value),
                },
            },
        } : undefined,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: mobile ? 12 : 16,
                    font: {
                        size: mobile ? 11 : 12,
                    },
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.dataset.label}: ${formatRupiah(context.raw)}`,
                },
            },
        },
    };
}

function branchComparisonOptions() {
    const options = commonOptions();
    const mobile = isMobileViewport();

    options.scales.x.ticks.maxTicksLimit = mobile ? 6 : 10;
    options.scales.x.ticks.callback = function (value) {
        const label = this.getLabelForValue(value);
        const maximumLength = mobile ? 14 : 22;

        return label.length > maximumLength
            ? `${label.slice(0, maximumLength - 1)}…`
            : label;
    };

    return options;
}

function create(key, root, configuration) {
    destroy(key);
    const canvas = root.querySelector(`[data-dashboard-chart="${key}"]`);

    if (!canvas || typeof window.Chart !== 'function') {
        throw new Error('Chart.js lokal belum dapat dimuat.');
    }

    instances.set(key, new window.Chart(canvas, configuration));
}

function destroy(key) {
    const chart = instances.get(key);

    if (chart) {
        chart.destroy();
        instances.delete(key);
    }
}

function toggleEmpty(root, key, empty) {
    const card = root.querySelector(`[data-chart-card="${key}"]`);
    const state = root.querySelector(`[data-chart-empty="${key}"]`);
    card?.classList.toggle('is-empty', Boolean(empty));

    if (state) {
        state.hidden = !empty;
    }

    return Boolean(empty);
}

function cssColor(variable) {
    return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
}

function withAlpha(hex, alpha) {
    const normalized = hex.replace('#', '');

    if (!/^[\da-f]{6}$/i.test(normalized)) {
        return `rgb(22 101 52 / ${alpha})`;
    }

    const value = Number.parseInt(normalized, 16);
    const red = (value >> 16) & 255;
    const green = (value >> 8) & 255;
    const blue = value & 255;
    return `rgb(${red} ${green} ${blue} / ${alpha})`;
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 768px)').matches;
}
