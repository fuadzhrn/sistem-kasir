(function (window, document) {
    'use strict';

    function updateBranchField() {
        const roleSelect = document.querySelector('[data-role-select]');
        const branchField = document.querySelector('[data-branch-field]');
        const branchSelect = document.querySelector('[data-branch-select]');

        if (!roleSelect || !branchField || !branchSelect) {
            return;
        }

        const option = roleSelect.options[roleSelect.selectedIndex];
        const isOwner = option && option.dataset.roleSlug === 'owner';

        branchField.hidden = isOwner;
        branchSelect.disabled = isOwner;
        branchSelect.required = !isOwner;

        if (isOwner) {
            branchSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.querySelector('[data-role-select]');
        const modal = document.getElementById('user-status-modal');

        if (roleSelect) {
            roleSelect.addEventListener('change', updateBranchField);
            updateBranchField();
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-user-status]');

            if (!trigger || !modal || !window.StoreApp || !window.StoreApp.modal) {
                return;
            }

            const isActivating = trigger.dataset.nextStatus === '1';
            const name = trigger.dataset.name || 'pengguna ini';
            const identity = [trigger.dataset.role, trigger.dataset.branch].filter(Boolean).join(' · ');
            const form = modal.querySelector('[data-status-form]');

            form.action = trigger.dataset.action;
            modal.querySelector('[data-status-value]').value = isActivating ? '1' : '0';
            modal.querySelector('[data-status-title]').textContent = isActivating
                ? 'Aktifkan pengguna'
                : 'Nonaktifkan pengguna';
            modal.querySelector('[data-status-message]').textContent = isActivating
                ? 'Aktifkan ' + name + ' (' + identity + ')?'
                : 'Nonaktifkan ' + name + ' (' + identity + ')? Pengguna akan ditolak pada request berikutnya.';
            modal.querySelector('[data-status-submit]').textContent = isActivating
                ? 'Ya, aktifkan'
                : 'Ya, nonaktifkan';

            window.StoreApp.modal.open(modal);
        });
    });
})(window, document);
