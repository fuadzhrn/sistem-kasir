(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.documentElement.classList.add('js-ready');

        document.addEventListener('click', function (event) {
            const dismissButton = event.target.closest('[data-alert-dismiss]');

            if (dismissButton) {
                const alert = dismissButton.closest('[data-alert]');

                if (alert) {
                    alert.remove();
                }
            }
        });

        document.querySelectorAll('[data-flash-toast]').forEach(function (alert) {
            if (!window.StoreApp || typeof window.StoreApp.showToast !== 'function') {
                return;
            }

            const message = alert.querySelector('.alert__message');

            window.StoreApp.showToast({
                type: alert.dataset.toastType || 'info',
                title: alert.dataset.toastTitle || 'Informasi',
                message: message ? message.textContent.trim() : '',
            });
        });

        document.dispatchEvent(new CustomEvent('store-app:ready'));
    });
})(window, document);
