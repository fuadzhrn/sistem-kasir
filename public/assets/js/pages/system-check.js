(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const clientTime = document.querySelector('[data-client-time]');
        const refreshButton = document.querySelector('[data-system-check-refresh]');

        if (clientTime && window.StoreApp.helpers) {
            clientTime.textContent = window.StoreApp.helpers.formatDateTime(new Date());
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function () {
                window.location.reload();
            });
        }
    });
})(window, document);
