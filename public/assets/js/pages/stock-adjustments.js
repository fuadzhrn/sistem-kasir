(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-adjustment-form]');
        const quantityFormatter = window.StoreApp && window.StoreApp.quantity;

        if (!form || !quantityFormatter) {
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
            return quantityFormatter.toMills(
                stocks[selectedBranchId() + ':' + productSelect.value] || '0',
            ) || 0;
        }

        function selectedUnit() {
            const option = productSelect.options[productSelect.selectedIndex];

            return option && option.value ? option.dataset.unit || '' : '';
        }

        function preview() {
            const before = currentStock();
            const quantity = quantityFormatter.toMills(quantityInput.value || '0') || 0;
            const target = quantityFormatter.toMills(targetInput.value || '0') || 0;
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
            const signedChange = change > 0
                ? '+' + quantityFormatter.formatMills(change)
                : quantityFormatter.formatMills(change);

            form.querySelector('[data-current-stock]').textContent = quantityFormatter.formatMills(before);
            form.querySelector('[data-current-unit]').textContent = unit;
            form.querySelector('[data-preview-before]').textContent = quantityFormatter.formatMills(before) + ' ' + unit;
            form.querySelector('[data-preview-change]').textContent = signedChange + ' ' + unit;
            form.querySelector('[data-preview-after]').textContent = quantityFormatter.formatMills(after) + ' ' + unit;

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
            const signedChange = values.change > 0
                ? '+' + quantityFormatter.formatMills(values.change)
                : quantityFormatter.formatMills(values.change);

            modal.querySelector('[data-confirm-adjustment-branch]').textContent = branchName || '-';
            modal.querySelector('[data-confirm-adjustment-product]').textContent = productName || '-';
            modal.querySelector('[data-confirm-adjustment-type]').textContent = typeName || '-';
            modal.querySelector('[data-confirm-adjustment-before]').textContent = quantityFormatter.formatMills(values.before) + ' ' + values.unit;
            modal.querySelector('[data-confirm-adjustment-change]').textContent = signedChange + ' ' + values.unit;
            modal.querySelector('[data-confirm-adjustment-after]').textContent = quantityFormatter.formatMills(values.after) + ' ' + values.unit;
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
