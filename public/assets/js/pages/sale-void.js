(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.querySelector('[data-sale-void-modal]');
        const openButton = document.querySelector('[data-sale-void-open]');
        const form = document.querySelector('[data-sale-void-form]');
        const submitButton = document.querySelector('[data-sale-void-submit]');

        openButton?.addEventListener('click', function () {
            window.StoreApp?.modal?.open(modal);
        });

        form?.addEventListener('submit', function () {
            if (! submitButton || ! form.checkValidity()) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Membatalkan...';
            submitButton.dataset.loading = 'true';
        });
    });
})(window, document);
