(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('branch-status-modal');

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-branch-status]');

            if (!trigger || !modal || !window.StoreApp || !window.StoreApp.modal) {
                return;
            }

            const isActivating = trigger.dataset.nextStatus === '1';
            const name = trigger.dataset.name || 'cabang ini';
            const form = modal.querySelector('[data-status-form]');

            form.action = trigger.dataset.action;
            modal.querySelector('[data-status-value]').value = isActivating ? '1' : '0';
            modal.querySelector('[data-status-title]').textContent = isActivating
                ? 'Aktifkan cabang'
                : 'Nonaktifkan cabang';
            modal.querySelector('[data-status-message]').textContent = isActivating
                ? 'Aktifkan ' + name + ' agar dapat digunakan kembali untuk operasi baru?'
                : 'Nonaktifkan ' + name + '? Cabang tidak dapat digunakan untuk operasi baru dan tindakan akan ditolak jika masih memiliki pengguna aktif.';
            modal.querySelector('[data-status-submit]').textContent = isActivating
                ? 'Ya, aktifkan'
                : 'Ya, nonaktifkan';

            window.StoreApp.modal.open(modal);
        });
    });
})(window, document);
