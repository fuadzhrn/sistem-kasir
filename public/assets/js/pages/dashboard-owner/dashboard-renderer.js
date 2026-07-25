import {
    createBadgeCell,
    createCell,
    createLinkCell,
    emptyRow,
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
    root.querySelector('[data-active-branch]').textContent = data.filters.branch_name;
    root.querySelector('[data-active-period]').textContent = data.filters.period_label;
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
        'Belum ada produk terjual pada periode ini.',
        (product) => {
            const row = document.createElement('tr');
            row.append(
                createCell(product.rank),
                productCell(product.name, product.code),
                createCell(`${product.quantity} ${product.unit}`),
                createCell(product.receipt_count),
                createCell(product.net_sales_formatted),
            );
            return row;
        });
}

function renderLowStocks(root, stocks) {
    renderRows(root.querySelector('[data-low-stocks]'), stocks, 5,
        'Tidak ada stok kritis saat ini.',
        (stock) => {
            const row = document.createElement('tr');
            row.append(
                createCell(stock.branch),
                createLinkCell(stock.product_name, stock.detail_url, stock.product_code),
                createCell(`${stock.quantity} ${stock.unit}`),
                createCell(`${stock.minimum_stock} ${stock.unit}`),
                createBadgeCell(stock.status, stock.status === 'Habis' ? 'danger' : 'warning'),
            );
            return row;
        });
}

function renderLatestTransactions(root, transactions) {
    renderRows(root.querySelector('[data-latest-transactions]'), transactions, 7,
        'Belum ada transaksi pada periode ini.',
        (sale) => {
            const row = document.createElement('tr');
            row.append(
                createLinkCell(sale.invoice_number, sale.detail_url),
                createCell(sale.transaction_date),
                createCell(sale.branch),
                createCell(sale.cashier),
                createCell(sale.payment_method),
                createCell(sale.total_formatted),
                createBadgeCell(sale.status, sale.status_variant),
            );
            return row;
        });
}

function renderLatestExpenses(root, expenses) {
    renderRows(root.querySelector('[data-latest-expenses]'), expenses, 7,
        'Belum ada pengeluaran pada periode ini.',
        (expense) => {
            const row = document.createElement('tr');
            row.append(
                createCell(expense.expense_date),
                createCell(expense.branch),
                createCell(expense.category),
                createLinkCell(expense.description, expense.detail_url),
                createCell(expense.creator),
                createCell(expense.amount_formatted),
                createBadgeCell(expense.status, expense.status_variant),
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

function productCell(name, code) {
    const cell = document.createElement('td');
    const strong = document.createElement('strong');
    const small = document.createElement('small');
    strong.textContent = name ?? '';
    small.textContent = code ?? '';
    cell.append(strong, small);
    return cell;
}
