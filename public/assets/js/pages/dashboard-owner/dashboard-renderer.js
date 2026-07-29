import {
    appendMobileDetail,
    createBadgeCell,
    createCell,
    createLinkCell,
    emptyRow,
    formatQuantity,
} from './dashboard-utils.js';

export function renderDashboard(root, data) {
    renderContext(root, data);
    renderCards(root, data.cards);
    renderTopProducts(root, data.top_products);
    renderLowStocks(root, data.low_stocks);
    renderLatestTransactions(root, data.latest_transactions);
    renderLatestExpenses(root, data.latest_expenses);
}

function renderContext(root, data) {
    const adminDashboard = root.hasAttribute('data-admin-dashboard');
    const dashboardName = adminDashboard ? 'Dashboard Admin' : 'Dashboard Owner';
    root.querySelector('[data-active-branch]').textContent = data.filters.branch_name;
    root.querySelector('[data-active-period]').textContent = data.filters.period_label;
    root.querySelectorAll('[data-active-filter-summary]').forEach((summary) => {
        summary.textContent = adminDashboard
            ? `${data.filters.branch_name} · ${data.filters.period_label}`
            : `${data.filters.period_label} · ${data.filters.branch_name}`;
    });
    const filterButton = root.querySelector(
        '[data-dashboard-filter-open], [data-admin-filter-open]',
    );

    if (filterButton) {
        filterButton.setAttribute(
            'aria-label',
            `Buka filter ${dashboardName}. Filter aktif: ${data.filters.period_label}, ${data.filters.branch_name}`,
        );
    }

    const generated = root.querySelector('[data-generated-at]');
    generated.textContent = data.generated_at_formatted;
    generated.dateTime = data.generated_at;
}

function renderCards(root, cards) {
    root.querySelectorAll('[data-financial-card]').forEach((card) => {
        const key = card.dataset.financialCard;
        const value = cards[key];

        if (!value) {
            return;
        }

        card.querySelector('[data-card-value]').textContent = value.formatted;
        card.classList.toggle(
            'is-negative',
            key === 'net_profit' && String(value.value).startsWith('-'),
        );
    });
}

function renderTopProducts(root, products) {
    renderRows(root.querySelector('[data-top-products]'), products, 5,
        'Belum ada produk terlaris pada periode ini.',
        (product) => {
            const row = document.createElement('tr');
            row.append(
                createCell(`#${product.rank}`, '', 'Peringkat'),
                productCell(product.name, product.code, 'Produk'),
                createCell(`${formatQuantity(product.quantity_value)} ${product.unit}`, '', 'Terjual'),
                createCell(product.receipt_count, '', 'Jumlah Nota'),
                createCell(product.net_sales_formatted, '', 'Penjualan Bersih'),
            );
            return row;
        });
}

function renderLowStocks(root, stocks) {
    renderRows(root.querySelector('[data-low-stocks]'), stocks, 5,
        'Tidak ada stok yang hampir habis.',
        (stock) => {
            const row = document.createElement('tr');
            row.append(
                createCell(stock.branch, '', 'Cabang'),
                createLinkCell(stock.product_name, stock.detail_url, stock.product_code, 'Produk'),
                createCell(`${formatQuantity(stock.quantity_value)} ${stock.unit}`, '', 'Tersedia'),
                createCell(`${formatQuantity(stock.minimum_stock_value)} ${stock.unit}`, '', 'Minimum'),
                createBadgeCell(
                    stock.status,
                    stock.status === 'Habis' ? 'danger' : 'warning',
                    'Status',
                ),
            );
            return row;
        });
}

function renderLatestTransactions(root, transactions) {
    renderRows(root.querySelector('[data-latest-transactions]'), transactions, 7,
        'Belum ada transaksi pada periode ini.',
        (sale) => {
            const row = document.createElement('tr');
            const status = createBadgeCell(sale.status, sale.status_variant, 'Status');
            appendMobileDetail(status, sale.detail_url);
            row.append(
                createLinkCell(sale.invoice_number, sale.detail_url, '', 'Nomor Nota'),
                createCell(sale.transaction_date, '', 'Tanggal dan Waktu'),
                createCell(sale.branch, '', 'Cabang'),
                createCell(sale.cashier, '', 'Kasir'),
                createCell(sale.payment_method, '', 'Pembayaran'),
                createCell(sale.total_formatted, '', 'Total'),
                status,
            );
            return row;
        });
}

function renderLatestExpenses(root, expenses) {
    renderRows(root.querySelector('[data-latest-expenses]'), expenses, 7,
        'Belum ada pengeluaran pada periode ini.',
        (expense) => {
            const row = document.createElement('tr');
            const status = createBadgeCell(expense.status, expense.status_variant, 'Status');
            appendMobileDetail(status, expense.detail_url);
            row.append(
                createCell(expense.expense_date, '', 'Tanggal'),
                createCell(expense.branch, '', 'Cabang'),
                createCell(expense.category, '', 'Kategori'),
                createLinkCell(expense.description, expense.detail_url, '', 'Deskripsi'),
                createCell(expense.creator, '', 'Pencatat'),
                createCell(expense.amount_formatted, '', 'Jumlah'),
                status,
            );
            return row;
        });
}

function renderRows(body, items, columnCount, message, factory) {
    body.replaceChildren();

    if (!Array.isArray(items) || items.length === 0) {
        body.append(emptyRow(columnCount, message));
        return;
    }

    const fragment = document.createDocumentFragment();
    items.forEach((item) => fragment.append(factory(item)));
    body.append(fragment);
}

function productCell(name, code, label = '') {
    const cell = document.createElement('td');
    const strong = document.createElement('strong');
    const small = document.createElement('small');
    strong.textContent = name ?? '';
    small.textContent = code ?? '';
    cell.append(strong, small);

    if (label) {
        cell.dataset.label = label;
    }

    return cell;
}
