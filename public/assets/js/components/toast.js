(function (window, document) {
    'use strict';

    const validTypes = ['success', 'warning', 'danger', 'info'];

    function ensureContainer() {
        let container = document.querySelector('[data-toast-container]');

        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.setAttribute('data-toast-container', '');
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }

        return container;
    }

    function showToast(options) {
        const settings = Object.assign({
            type: 'info',
            title: 'Informasi',
            message: '',
            duration: 4000,
        }, options || {});
        const type = validTypes.includes(settings.type) ? settings.type : 'info';
        const container = ensureContainer();
        const toast = document.createElement('article');
        const status = document.createElement('span');
        const content = document.createElement('div');
        const title = document.createElement('p');
        const message = document.createElement('p');
        const closeButton = document.createElement('button');
        const statusLabels = {
            success: '✓',
            warning: '!',
            danger: '×',
            info: 'i',
        };

        toast.className = 'toast toast--' + type;
        toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        status.className = 'toast__status';
        status.setAttribute('aria-hidden', 'true');
        status.textContent = statusLabels[type];
        content.className = 'toast__content';
        title.className = 'toast__title';
        title.textContent = settings.title;
        message.className = 'toast__message';
        message.textContent = settings.message;
        closeButton.className = 'toast__close';
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Tutup notifikasi');
        closeButton.textContent = '×';

        content.appendChild(title);
        content.appendChild(message);
        toast.appendChild(status);
        toast.appendChild(content);
        toast.appendChild(closeButton);
        container.appendChild(toast);

        let timeoutId = window.setTimeout(function () {
            toast.remove();
        }, settings.duration);

        closeButton.addEventListener('click', function () {
            window.clearTimeout(timeoutId);
            toast.remove();
        });

        return toast;
    }

    window.showToast = showToast;
    window.StoreApp = window.StoreApp || {};
    window.StoreApp.showToast = showToast;
})(window, document);
