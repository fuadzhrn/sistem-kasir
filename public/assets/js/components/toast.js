(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};
    window.StoreApp.toast = function (message) {
        const toast = document.createElement('div');

        toast.className = 'toast';
        toast.setAttribute('role', 'status');
        toast.textContent = message;
        document.body.appendChild(toast);

        window.setTimeout(function () {
            toast.remove();
        }, 3000);
    };
})(window, document);
