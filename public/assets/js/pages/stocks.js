(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-initial-stock-form]');
        const modal = document.getElementById('initial-stock-confirmation-modal');
        const confirmButton = document.querySelector('[data-confirm-initial-stock]');
        const quantity = window.StoreApp && window.StoreApp.quantity;
        let confirmed = false;

        if (!form || !modal || !window.StoreApp || !quantity) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (confirmed) {
                const submitButton = form.querySelector('[data-initial-stock-submit]');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.dataset.loading = 'true';
                }

                return;
            }

            if (!form.reportValidity()) {
                event.preventDefault();

                return;
            }

            event.preventDefault();

            const quantityInput = form.querySelector('[name="quantity"]');
            const reasonInput = form.querySelector('[name="reason"]');
            const before = quantity.toMills(form.dataset.quantityBefore || '0') || 0;
            const after = quantity.toMills(quantityInput ? quantityInput.value : '0');

            if (after === null) {
                quantityInput.setCustomValidity('Gunakan quantity dengan maksimal tiga angka desimal.');
                quantityInput.reportValidity();

                return;
            }

            quantityInput.setCustomValidity('');
            const change = after - before;
            const unit = form.dataset.unit || '';
            const signedChange = change > 0
                ? `+${quantity.formatMills(change)}`
                : quantity.formatMills(change);

            modal.querySelector('[data-confirm-branch]').textContent = form.dataset.branchName || '—';
            modal.querySelector('[data-confirm-product]').textContent = form.dataset.productName || '—';
            modal.querySelector('[data-confirm-before]').textContent = `${quantity.formatMills(before)} ${unit}`;
            modal.querySelector('[data-confirm-after]').textContent = `${quantity.formatMills(after)} ${unit}`;
            modal.querySelector('[data-confirm-change]').textContent = `${signedChange} ${unit}`;
            modal.querySelector('[data-confirm-reason]').textContent = reasonInput ? reasonInput.value.trim() : '—';
            window.StoreApp.modal.open(modal);
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', function () {
                confirmed = true;
                window.StoreApp.modal.close(modal);
                form.requestSubmit();
            });
        }
    });
})(window, document);
