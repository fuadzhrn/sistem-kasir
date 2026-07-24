(function (window) {
    'use strict';

    window.StoreApp = window.StoreApp || {};

    window.StoreApp.helpers = {
        formatDateTime: function (date) {
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'medium',
            }).format(date);
        },
        debounce: function (callback, delay) {
            let timeoutId;

            return function () {
                const args = arguments;

                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(function () {
                    callback.apply(null, args);
                }, delay);
            };
        },
        storage: {
            get: function (key) {
                try {
                    return window.localStorage.getItem(key);
                } catch (error) {
                    return null;
                }
            },
            set: function (key, value) {
                try {
                    window.localStorage.setItem(key, value);
                } catch (error) {
                    return false;
                }

                return true;
            },
        },
    };
})(window);
