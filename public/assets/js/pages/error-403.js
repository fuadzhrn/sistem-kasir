(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const backButton = document.querySelector('[data-history-back]');

        if (!backButton) {
            return;
        }

        backButton.addEventListener('click', function () {
            window.history.back();
        });
    });
})(window, document);
