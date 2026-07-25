(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const statusModal = document.getElementById('payment-status-modal');
        const deleteModal = document.getElementById('payment-delete-modal');

        document.addEventListener('click', function (event) {
            const statusTrigger = event.target.closest('[data-payment-status]');
            const deleteTrigger = event.target.closest('[data-payment-delete]');

            if (statusTrigger && statusModal && window.StoreApp) {
                const activating = statusTrigger.dataset.nextStatus === '1';
                statusModal.querySelector('[data-status-form]').action = statusTrigger.dataset.action;
                statusModal.querySelector('[data-status-value]').value = statusTrigger.dataset.nextStatus;
                statusModal.querySelector('[data-status-title]').textContent = activating ? 'Aktifkan metode pembayaran' : 'Nonaktifkan metode pembayaran';
                statusModal.querySelector('[data-status-message]').textContent = activating
                    ? `Aktifkan kembali ${statusTrigger.dataset.name}?`
                    : `Nonaktifkan ${statusTrigger.dataset.name}? Metode ini tidak tersedia untuk transaksi baru.`;
                window.StoreApp.modal.open(statusModal);
            }

            if (deleteTrigger && deleteModal && window.StoreApp) {
                deleteModal.querySelector('[data-delete-form]').action = deleteTrigger.dataset.action;
                deleteModal.querySelector('[data-delete-name]').textContent = deleteTrigger.dataset.name;
                window.StoreApp.modal.open(deleteModal);
            }
        });
    });
})(window, document);
