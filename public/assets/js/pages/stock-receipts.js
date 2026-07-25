(function (window, document) {
    'use strict';

    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    function numericValue(value) {
        const parsed = Number.parseFloat(String(value || '').replace(',', '.'));

        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatRupiah(value) {
        return rupiahFormatter.format(numericValue(value)).replace(/\s/g, '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-stock-receipt-form]');

        if (!form) {
            return;
        }

        const itemsContainer = form.querySelector('[data-receipt-items]');
        const template = form.querySelector('[data-receipt-item-template]');
        const addButton = form.querySelector('[data-add-receipt-item]');
        const productSearch = form.querySelector('[data-product-search]');
        const modal = document.getElementById('receipt-confirmation-modal');
        const confirmationButton = document.querySelector('[data-confirm-stock-receipt]');
        let rowSequence = itemsContainer.querySelectorAll('[data-receipt-item-row]').length;
        let confirmed = false;

        function rows() {
            return Array.from(itemsContainer.querySelectorAll('[data-receipt-item-row]'));
        }

        function updateProductMetadata(row) {
            const select = row.querySelector('[data-product-select]');
            const option = select.options[select.selectedIndex];

            row.querySelector('[data-product-code]').textContent = option && option.value ? option.dataset.code : '-';
            row.querySelector('[data-product-size]').textContent = option && option.value ? option.dataset.size : '-';
            row.querySelector('[data-product-unit]').textContent = option && option.value ? option.dataset.unit : '-';
        }

        function updateTotals() {
            let total = 0;

            rows().forEach(function (row) {
                const quantity = numericValue(row.querySelector('[data-item-quantity]').value);
                const price = numericValue(row.querySelector('[data-item-price]').value);
                const subtotal = quantity > 0 && price > 0 ? quantity * price : 0;

                row.querySelector('[data-item-subtotal]').textContent = formatRupiah(subtotal);
                total += subtotal;
            });

            form.querySelector('[data-receipt-total]').textContent = formatRupiah(total);
            form.querySelector('[data-receipt-item-count]').textContent = String(rows().length);
        }

        function validateDuplicates(showToast) {
            const seen = new Set();
            let valid = true;

            rows().forEach(function (row) {
                const select = row.querySelector('[data-product-select]');
                const productId = select.value;
                const duplicate = productId !== '' && seen.has(productId);

                select.setCustomValidity(duplicate ? 'Produk ini sudah dipilih pada baris lain.' : '');
                row.classList.toggle('receipt-item-duplicate', duplicate);
                valid = valid && !duplicate;

                if (productId !== '') {
                    seen.add(productId);
                }
            });

            if (!valid && showToast && window.StoreApp && window.StoreApp.showToast) {
                window.StoreApp.showToast({
                    type: 'danger',
                    title: 'Produk duplikat',
                    message: 'Satu produk hanya boleh dipilih satu kali.'
                });
            }

            return valid;
        }

        function bindRow(row) {
            const select = row.querySelector('[data-product-select]');

            select.addEventListener('change', function () {
                updateProductMetadata(row);
                validateDuplicates(true);
            });

            row.querySelectorAll('[data-item-quantity], [data-item-price]').forEach(function (input) {
                input.addEventListener('input', updateTotals);
            });

            row.querySelector('[data-remove-receipt-item]').addEventListener('click', function () {
                if (rows().length === 1) {
                    if (window.StoreApp && window.StoreApp.showToast) {
                        window.StoreApp.showToast({
                            type: 'warning',
                            title: 'Minimal satu produk',
                            message: 'Dokumen barang masuk harus memiliki minimal satu item.'
                        });
                    }

                    return;
                }

                row.remove();
                validateDuplicates(false);
                updateTotals();
            });

            updateProductMetadata(row);
        }

        rows().forEach(bindRow);
        updateTotals();

        addButton.addEventListener('click', function () {
            if (rows().length >= 100) {
                if (window.StoreApp && window.StoreApp.showToast) {
                    window.StoreApp.showToast({
                        type: 'warning',
                        title: 'Batas item tercapai',
                        message: 'Maksimal 100 jenis produk dalam satu penerimaan.'
                    });
                }

                return;
            }

            const markup = template.innerHTML.replaceAll('__INDEX__', String(rowSequence++));
            itemsContainer.insertAdjacentHTML('beforeend', markup);
            const newRow = rows().at(-1);

            bindRow(newRow);
            updateTotals();
            newRow.querySelector('[data-product-select]').focus();
        });

        if (productSearch) {
            productSearch.addEventListener('input', function () {
                const term = productSearch.value.trim().toLocaleLowerCase('id-ID');

                rows().forEach(function (row) {
                    const select = row.querySelector('[data-product-select]');

                    Array.from(select.options).forEach(function (option) {
                        if (option.value === '' || option.selected) {
                            option.hidden = false;

                            return;
                        }

                        option.hidden = term !== '' && !String(option.dataset.search || '').includes(term);
                    });
                });
            });
        }

        form.addEventListener('submit', function (event) {
            if (confirmed) {
                const submitButton = form.querySelector('[data-receipt-submit]');

                submitButton.disabled = true;
                submitButton.dataset.loading = 'true';
                submitButton.textContent = 'Menyimpan...';

                return;
            }

            event.preventDefault();

            if (!validateDuplicates(true) || !form.reportValidity()) {
                return;
            }

            const branchSelect = form.querySelector('[data-receipt-branch]');
            const branchName = branchSelect
                ? branchSelect.options[branchSelect.selectedIndex].textContent.trim()
                : form.dataset.branchName;
            const supplier = form.querySelector('[data-receipt-supplier]').value.trim();
            const notes = form.querySelector('[data-receipt-notes]').value.trim();

            modal.querySelector('[data-confirm-receipt-branch]').textContent = branchName || '-';
            modal.querySelector('[data-confirm-receipt-date]').textContent = form.querySelector('[data-receipt-date]').value;
            modal.querySelector('[data-confirm-receipt-supplier]').textContent = supplier || 'Tidak dicantumkan';
            modal.querySelector('[data-confirm-receipt-count]').textContent = rows().length + ' produk';
            modal.querySelector('[data-confirm-receipt-total]').textContent = form.querySelector('[data-receipt-total]').textContent;
            modal.querySelector('[data-confirm-receipt-notes]').textContent = notes || 'Tidak ada catatan';

            if (window.StoreApp && window.StoreApp.modal) {
                window.StoreApp.modal.open(modal);
            }
        });

        confirmationButton.addEventListener('click', function () {
            confirmed = true;

            if (window.StoreApp && window.StoreApp.modal) {
                window.StoreApp.modal.close(modal);
            }

            form.requestSubmit();
        });
    });
})(window, document);
