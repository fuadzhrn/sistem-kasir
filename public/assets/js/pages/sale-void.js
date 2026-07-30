(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.querySelector('[data-sale-void-modal]');
        const openButton = document.querySelector('[data-sale-void-open]');
        const form = document.querySelector('[data-sale-void-form]');
        const submitButton = document.querySelector('[data-sale-void-submit]');
        let isSubmitting = false;

        openButton?.addEventListener('click', function () {
            window.StoreApp?.modal?.open(modal);
        });

        if (modal?.hasAttribute('data-sale-void-reopen')) {
            window.StoreApp?.modal?.open(modal);
        }

        form?.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();

                return;
            }

            if (! submitButton || ! form.checkValidity()) {
                return;
            }

            isSubmitting = true;
            form.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            submitButton.textContent = 'Membatalkan...';
            submitButton.dataset.loading = 'true';
        });

        window.addEventListener('pageshow', function () {
            isSubmitting = false;
            form?.setAttribute('aria-busy', 'false');

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Batalkan Transaksi';
                delete submitButton.dataset.loading;
            }
        });
    });
})(window, document);
