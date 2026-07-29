import {
    debounce,
    formatQuantity,
    formatRupiah,
    moneyToCents,
    showToast,
} from './cashier-utils.js';

export function createProductBrowser(root, store) {
    const grid = root.querySelector('[data-product-grid]');
    const template = document.getElementById('cashier-product-card-template');
    const loading = root.querySelector('[data-product-loading]');
    const empty = root.querySelector('[data-product-empty]');
    const emptyMessage = root.querySelector('[data-product-empty-message]');
    const count = root.querySelector('[data-product-count]');
    const loadMore = root.querySelector('[data-load-more]');
    const searchInput = root.querySelector('[data-product-search]');
    const clearButton = root.querySelector('[data-search-clear]');
    const categoryButtons = Array.from(root.querySelectorAll('[data-category-id]'));
    const branchId = root.dataset.branchId;
    const shouldSendBranch = root.dataset.canSwitchBranch === '1';
    const endpoint = root.dataset.productsUrl;
    const placeholder = root.dataset.placeholderUrl;
    const products = new Map();
    let currentPage = 1;
    let lastPage = 1;
    let search = '';
    let categoryId = '';
    let requestController = null;

    async function fetchPage(reset = true, overrideSearch = null) {
        if (!branchId) {
            return { data: [], meta: { current_page: 1, last_page: 1, total: 0 } };
        }

        if (requestController) {
            requestController.abort();
        }

        requestController = new AbortController();
        const targetPage = reset ? 1 : currentPage + 1;
        const params = new URLSearchParams({
            page: String(targetPage),
            per_page: '24',
        });

        if (shouldSendBranch) {
            params.set('branch_id', branchId);
        }

        const effectiveSearch = overrideSearch === null ? search : overrideSearch;

        if (effectiveSearch) {
            params.set('search', effectiveSearch);
        }

        if (categoryId && overrideSearch === null) {
            params.set('category_id', categoryId);
        }

        loading.hidden = false;
        empty.hidden = true;
        loadMore.disabled = true;

        try {
            const response = await window.fetch(endpoint + '?' + params.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error('Produk tidak dapat dimuat.');
            }

            const payload = await response.json();

            if (overrideSearch !== null) {
                return payload;
            }

            currentPage = payload.meta.current_page;
            lastPage = payload.meta.last_page;
            payload.data.forEach(function (product) {
                products.set(product.id, product);
            });
            renderProducts(payload.data, reset, payload.meta.total);

            return payload;
        } catch (error) {
            if (error.name !== 'AbortError') {
                showToast('danger', 'Gagal memuat produk', error.message);
                empty.hidden = false;
                emptyMessage.textContent = 'Produk tidak dapat dimuat. Silakan coba kembali.';
            }

            return { data: [], meta: { current_page: 1, last_page: 1, total: 0 } };
        } finally {
            loading.hidden = true;
            loadMore.disabled = false;
        }
    }

    function renderProducts(data, reset, total) {
        if (reset) {
            grid.replaceChildren();
        }

        const existingIds = new Set(
            Array.from(grid.querySelectorAll('[data-product-id]')).map(function (card) {
                return Number(card.dataset.productId);
            }),
        );
        const fragment = document.createDocumentFragment();

        data.forEach(function (product) {
            if (existingIds.has(product.id)) {
                return;
            }

            const card = template.content.firstElementChild.cloneNode(true);
            const image = card.querySelector('[data-product-image]');
            const status = card.querySelector('[data-product-status]');
            const addButton = card.querySelector('[data-add-product]');
            card.dataset.productId = String(product.id);
            card.classList.toggle('is-unavailable', !product.is_available);
            image.src = product.image_url || placeholder;
            image.alt = 'Foto ' + product.name;
            image.addEventListener('error', function () {
                image.src = placeholder;
            }, { once: true });
            status.textContent = product.stock_status_label;
            status.classList.add(
                product.stock_status === 'safe'
                    ? 'badge-success'
                    : (product.stock_status === 'low' ? 'badge-warning' : 'badge-danger'),
            );
            card.querySelector('[data-product-code]').textContent = product.code;
            card.querySelector('[data-product-name]').textContent = product.name;
            card.querySelector('[data-product-meta]').textContent = [
                product.brand,
                product.size,
                product.unit_symbol || product.unit_name,
            ].filter(Boolean).join(' • ') || product.category_name;
            card.querySelector('[data-product-barcode]').textContent = product.barcode
                ? 'Barcode: ' + product.barcode
                : 'Tanpa barcode';
            card.querySelector('[data-product-stock]').textContent = formatQuantity(product.stock_quantity) + ' ' + (product.unit_symbol || product.unit_name);
            card.querySelector('[data-product-price]').textContent = formatRupiah(moneyToCents(product.selling_price));
            addButton.disabled = !product.is_available;
            addButton.textContent = product.is_available ? 'Tambah' : 'Stok Habis';
            addButton.setAttribute(
                'aria-label',
                product.is_available
                    ? 'Tambah ' + product.name + ' ke keranjang'
                    : product.name + ' tidak dapat ditambahkan karena stok habis',
            );
            fragment.appendChild(card);
        });

        grid.appendChild(fragment);
        count.textContent = String(total) + ' produk';
        empty.hidden = grid.children.length > 0;
        emptyMessage.textContent = 'Ubah kata pencarian atau kategori untuk melihat hasil lain.';
        loadMore.hidden = currentPage >= lastPage || grid.children.length === 0;
    }

    grid.addEventListener('click', function (event) {
        const button = event.target.closest('[data-add-product]');
        const card = event.target.closest('[data-product-id]');

        if (!button || !card) {
            return;
        }

        const product = products.get(Number(card.dataset.productId));

        if (product && store.add(product)) {
            showToast('success', 'Produk ditambahkan', product.name + ' masuk ke keranjang sementara.');
            searchInput.focus();
        }
    });

    const runSearch = debounce(function () {
        search = searchInput.value.trim();
        clearButton.hidden = search === '';
        fetchPage(true);
    }, 300);

    searchInput.addEventListener('input', runSearch);
    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            search = searchInput.value.trim();
            clearButton.hidden = search === '';
            fetchPage(true);
        }
    });
    clearButton.addEventListener('click', function () {
        searchInput.value = '';
        search = '';
        clearButton.hidden = true;
        fetchPage(true);
        searchInput.focus();
    });
    categoryButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            categoryId = button.dataset.categoryId;
            categoryButtons.forEach(function (candidate) {
                const active = candidate === button;
                candidate.classList.toggle('is-active', active);
                candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            fetchPage(true);
        });
    });
    loadMore.addEventListener('click', function () {
        fetchPage(false);
    });

    async function lookupStoredProduct(stored) {
        const payload = await fetchPage(true, stored.code);

        return payload.data.find(function (product) {
            return product.id === stored.product_id;
        }) || null;
    }

    return {
        focusSearch: function () {
            searchInput.focus();
        },
        load: function () {
            return fetchPage(true);
        },
        lookupStoredProduct,
        reload: function () {
            return fetchPage(true);
        },
    };
}
