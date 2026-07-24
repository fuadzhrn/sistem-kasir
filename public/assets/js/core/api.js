(function (window) {
    'use strict';

    window.StoreApp = window.StoreApp || {};

    window.StoreApp.api = function (url, options) {
        const requestOptions = Object.assign({}, options || {});
        const headers = new Headers(requestOptions.headers || {});
        const csrfToken = window.StoreApp.csrfToken ? window.StoreApp.csrfToken() : '';

        headers.set('Accept', 'application/json');

        if (csrfToken) {
            headers.set('X-CSRF-TOKEN', csrfToken);
        }

        requestOptions.headers = headers;

        return window.fetch(url, requestOptions);
    };
})(window);
