(function (window, document) {
    'use strict';

    const formatQuantity = function (value) {
        const number = Number(value || 0);

        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 3
        }).format(Number.isFinite(number) ? number : 0);
    };

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-initial-stock-form]');
        const modal = document.getElementById('initial-stock-confirmation-modal');
        const confirmButton = document.querySelector('[data-confirm-initial-stock]');
        let confirmed = false;

        if (!form || !modal || !window.StoreApp) {
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
            const before = Number(form.dataset.quantityBefore || 0);
            const after = Number(quantityInput ? quantityInput.value : 0);
            const change = after - before;
            const unit = form.dataset.unit || '';
            const signedChange = change > 0 ? `+${formatQuantity(change)}` : formatQuantity(change);

            modal.querySelector('[data-confirm-branch]').textContent = form.dataset.branchName || '—';
            modal.querySelector('[data-confirm-product]').textContent = form.dataset.productName || '—';
            modal.querySelector('[data-confirm-before]').textContent = `${formatQuantity(before)} ${unit}`;
            modal.querySelector('[data-confirm-after]').textContent = `${formatQuantity(after)} ${unit}`;
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
