(function (window, document) {
    'use strict';

    const formatQuantity = function (value) {
        const number = Number(value || 0);

        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 3
        }).format(Number.isFinite(number) ? number : 0);
    };

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-adjustment-form]');

        if (!form) {
            return;
        }

        const branchSelect = form.querySelector('[data-adjustment-branch]');
        const productSelect = form.querySelector('[data-adjustment-product]');
        const typeSelect = form.querySelector('[data-adjustment-type]');
        const quantityInput = form.querySelector('[data-adjustment-quantity]');
        const targetInput = form.querySelector('[data-adjustment-target]');
        const quantityGroup = form.querySelector('[data-quantity-group]');
        const targetGroup = form.querySelector('[data-target-group]');
        const reasonInput = form.querySelector('[data-adjustment-reason]');
        const modal = document.getElementById('adjustment-confirmation-modal');
        const confirmButton = document.querySelector('[data-confirm-adjustment]');
        let confirmed = false;
        let stocks = {};

        try {
            stocks = JSON.parse(form.dataset.stockQuantities || '{}');
        } catch (error) {
            stocks = {};
        }

        function selectedBranchId() {
            return branchSelect ? branchSelect.value : form.dataset.branchId;
        }

        function currentStock() {
            return Number(stocks[selectedBranchId() + ':' + productSelect.value] || 0);
        }

        function selectedUnit() {
            const option = productSelect.options[productSelect.selectedIndex];

            return option && option.value ? option.dataset.unit || '' : '';
        }

        function preview() {
            const before = currentStock();
            const quantity = Number(quantityInput.value || 0);
            const target = Number(targetInput.value || 0);
            const type = typeSelect.value;
            let change = 0;

            if (type === 'addition') {
                change = quantity;
            } else if (['subtraction', 'damaged', 'lost'].includes(type)) {
                change = -quantity;
            } else if (type === 'correction') {
                change = target - before;
            }

            const after = before + change;
            const unit = selectedUnit();
            const signedChange = change > 0 ? '+' + formatQuantity(change) : formatQuantity(change);

            form.querySelector('[data-current-stock]').textContent = formatQuantity(before);
            form.querySelector('[data-current-unit]').textContent = unit;
            form.querySelector('[data-preview-before]').textContent = formatQuantity(before) + ' ' + unit;
            form.querySelector('[data-preview-change]').textContent = signedChange + ' ' + unit;
            form.querySelector('[data-preview-after]').textContent = formatQuantity(after) + ' ' + unit;

            return { before: before, change: change, after: after, unit: unit };
        }

        function toggleQuantityFields() {
            const correction = typeSelect.value === 'correction';

            quantityGroup.hidden = correction;
            quantityInput.disabled = correction;
            quantityInput.required = !correction;
            targetGroup.hidden = !correction;
            targetInput.disabled = !correction;
            targetInput.required = correction;
            preview();
        }

        [branchSelect, productSelect, quantityInput, targetInput].filter(Boolean).forEach(function (field) {
            field.addEventListener('input', preview);
            field.addEventListener('change', preview);
        });
        typeSelect.addEventListener('change', toggleQuantityFields);

        toggleQuantityFields();

        form.addEventListener('submit', function (event) {
            if (confirmed) {
                const submitButton = form.querySelector('[data-adjustment-submit]');

                submitButton.disabled = true;
                submitButton.dataset.loading = 'true';
                submitButton.textContent = 'Menyimpan...';

                return;
            }

            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const values = preview();
            const branchName = branchSelect
                ? branchSelect.options[branchSelect.selectedIndex].textContent.trim()
                : form.dataset.branchName;
            const productName = productSelect.options[productSelect.selectedIndex].textContent.trim();
            const typeName = typeSelect.options[typeSelect.selectedIndex].textContent.trim();
            const signedChange = values.change > 0 ? '+' + formatQuantity(values.change) : formatQuantity(values.change);

            modal.querySelector('[data-confirm-adjustment-branch]').textContent = branchName || '-';
            modal.querySelector('[data-confirm-adjustment-product]').textContent = productName || '-';
            modal.querySelector('[data-confirm-adjustment-type]').textContent = typeName || '-';
            modal.querySelector('[data-confirm-adjustment-before]').textContent = formatQuantity(values.before) + ' ' + values.unit;
            modal.querySelector('[data-confirm-adjustment-change]').textContent = signedChange + ' ' + values.unit;
            modal.querySelector('[data-confirm-adjustment-after]').textContent = formatQuantity(values.after) + ' ' + values.unit;
            modal.querySelector('[data-confirm-adjustment-reason]').textContent = reasonInput.value.trim();

            if (window.StoreApp && window.StoreApp.modal) {
                window.StoreApp.modal.open(modal);
            }
        });

        confirmButton.addEventListener('click', function () {
            confirmed = true;

            if (window.StoreApp && window.StoreApp.modal) {
                window.StoreApp.modal.close(modal);
            }

            form.requestSubmit();
        });
    });
})(window, document);
