(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};
    window.StoreApp.confirm = function (message) {
        const dialog = document.getElementById('modal-confirm');

        if (!dialog) {
            return Promise.resolve(window.confirm(message));
        }

        const messageElement = dialog.querySelector('[data-confirm-message]');
        const acceptButton = dialog.querySelector('[data-confirm-accept]');
        const cancelButton = dialog.querySelector('[data-confirm-cancel]');

        messageElement.textContent = message;
        window.StoreApp.modal.open(dialog);

        return new Promise(function (resolve) {
            acceptButton.addEventListener('click', function accept() {
                window.StoreApp.modal.close(dialog);
                resolve(true);
            }, { once: true });

            cancelButton.addEventListener('click', function cancel() {
                window.StoreApp.modal.close(dialog);
                resolve(false);
            }, { once: true });
        });
    };
})(window, document);
