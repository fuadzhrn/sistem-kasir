(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};

    let pendingResolve = null;

    window.StoreApp.confirm = function (message) {
        const modal = document.getElementById('modal-confirm');

        if (!modal || !window.StoreApp.modal) {
            return Promise.resolve(false);
        }

        const messageElement = modal.querySelector('[data-confirm-message]');

        if (messageElement && message) {
            messageElement.textContent = message;
        }

        window.StoreApp.modal.open(modal);

        return new Promise(function (resolve) {
            pendingResolve = resolve;
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modal-confirm');

        if (!modal) {
            return;
        }

        const acceptButton = modal.querySelector('[data-confirm-accept]');

        if (acceptButton) {
            acceptButton.addEventListener('click', function () {
                if (pendingResolve) {
                    pendingResolve(true);
                    pendingResolve = null;
                }

                document.dispatchEvent(new CustomEvent('store-app:confirmed'));
                window.StoreApp.modal.close(modal, 'confirm');
            });
        }

        modal.addEventListener('store-app:modal-closed', function (event) {
            if (pendingResolve && event.detail.reason !== 'confirm') {
                pendingResolve(false);
                pendingResolve = null;
            }
        });
    });
})(window, document);
