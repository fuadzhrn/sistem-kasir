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

        document.dispatchEvent(new CustomEvent('store-app:ready'));
    });
})(window, document);
