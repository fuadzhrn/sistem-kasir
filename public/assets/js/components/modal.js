(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};
    window.StoreApp.modal = {
        open: function (dialog) {
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
        },
        close: function (dialog) {
            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        },
    };
})(window, document);
