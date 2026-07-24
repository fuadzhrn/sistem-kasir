(function (window, document) {
    'use strict';

    const toastMessages = {
        success: {
            title: 'Berhasil',
            message: 'Data contoh berhasil diproses.',
        },
        warning: {
            title: 'Perlu perhatian',
            message: 'Periksa kembali informasi contoh.',
        },
        danger: {
            title: 'Tidak dapat diproses',
            message: 'Ini adalah feedback error untuk demonstrasi.',
        },
        info: {
            title: 'Informasi',
            message: 'Komponen ini tidak menjalankan fitur bisnis.',
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const toastTrigger = event.target.closest('[data-toast-demo]');

            if (!toastTrigger || typeof window.showToast !== 'function') {
                return;
            }

            const type = toastTrigger.getAttribute('data-toast-demo') || 'info';
            const content = toastMessages[type] || toastMessages.info;

            window.showToast({
                type: type,
                title: content.title,
                message: content.message,
            });
        });

        document.querySelectorAll('[data-demo-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        });

        document.addEventListener('store-app:confirmed', function () {
            if (typeof window.showToast === 'function') {
                window.showToast({
                    type: 'success',
                    title: 'Konfirmasi diterima',
                    message: 'Tindakan demo selesai tanpa perubahan data.',
                });
            }
        });
    });
})(window, document);
