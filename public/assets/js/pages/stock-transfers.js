(function (document) {
    'use strict';

    function formatQuantity(value) {
        const number = Number.parseFloat(value);

        if (!Number.isFinite(number)) {
            return '0';
        }

        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        }).format(number);
    }

    function initializeTransferForm(form) {
        const source = form.querySelector('[data-transfer-source]');
        const destination = form.querySelector('[data-transfer-destination]');
        const product = form.querySelector('[data-transfer-product]');
        const stockOutput = form.querySelector('[data-transfer-current-stock]');
        const unitOutput = form.querySelector('[data-transfer-unit]');
        const submitButton = form.querySelector('[data-transfer-submit]');
        let quantities = {};

        try {
            quantities = JSON.parse(form.dataset.stockQuantities || '{}');
        } catch (error) {
            quantities = {};
        }

        function sourceId() {
            return source ? source.value : form.dataset.sourceId;
        }

        function updateDestinationOptions() {
            const currentSource = sourceId();

            Array.from(destination.options).forEach(function (option) {
                option.disabled = option.value !== '' && option.value === currentSource;
            });

            if (destination.value === currentSource) {
                destination.value = '';
            }
        }

        function updateStockPreview() {
            const key = sourceId() + ':' + product.value;
            const selectedProduct = product.options[product.selectedIndex];
            stockOutput.textContent = formatQuantity(quantities[key] || '0');
            unitOutput.textContent = selectedProduct ? selectedProduct.dataset.unit || '' : '';
        }

        if (source) {
            source.addEventListener('change', function () {
                updateDestinationOptions();
                updateStockPreview();
            });
        }

        destination.addEventListener('change', updateDestinationOptions);
        product.addEventListener('change', updateStockPreview);
        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
        });
        updateDestinationOptions();
        updateStockPreview();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-transfer-form]').forEach(initializeTransferForm);
        document.querySelectorAll('[data-transfer-action-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                    button.textContent = 'Memproses...';
                });
            });
        });
    });
})(document);
