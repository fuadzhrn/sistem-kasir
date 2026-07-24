(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.documentElement.classList.add('js-ready');
        document.dispatchEvent(new CustomEvent('store-app:ready'));
    });
})();
