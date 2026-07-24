(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};
    window.StoreApp.csrfToken = function () {
        const tokenElement = document.querySelector('meta[name="csrf-token"]');

        return tokenElement ? tokenElement.getAttribute('content') : '';
    };
})(window, document);
