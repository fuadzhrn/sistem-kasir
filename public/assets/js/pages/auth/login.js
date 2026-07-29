(function (document) {
    'use strict';

    const roleLabels = {
        owner: 'Owner',
        admin: 'Admin Cabang',
        cashier: 'Kasir',
    };

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-login-form]');
        const submitButton = document.querySelector('[data-login-submit]');
        const submitLabel = submitButton
            ? submitButton.querySelector('[data-login-submit-label]')
            : null;
        const context = document.querySelector('[data-login-context]');
        const roleInputs = Array.from(document.querySelectorAll('input[name="login_role"]'));
        let isSubmitting = false;

        if (!form || !submitButton || !submitLabel || roleInputs.length === 0) {
            return;
        }

        function selectedRole() {
            const selected = roleInputs.find(function (input) {
                return input.checked;
            });

            return selected ? selected.value : '';
        }

        function updateRoleState() {
            const role = selectedRole();
            const label = roleLabels[role];

            roleInputs.forEach(function (input) {
                const card = input.nextElementSibling;

                if (card) {
                    card.classList.toggle('auth-role-card--active', input.checked);
                }
            });

            submitButton.disabled = !label;
            submitButton.setAttribute('aria-disabled', String(!label));
            submitButton.dataset.selectedRole = role;
            submitLabel.textContent = label
                ? 'Masuk sebagai ' + label
                : 'Pilih Jenis Akun Terlebih Dahulu';

            if (context) {
                context.textContent = label
                    ? 'Masuk sebagai ' + label
                    : 'Belum ada jenis akun yang dipilih';
            }
        }

        roleInputs.forEach(function (input) {
            input.addEventListener('change', updateRoleState);
        });

        form.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            if (!selectedRole()) {
                event.preventDefault();
                roleInputs[0].focus();
                return;
            }

            isSubmitting = true;
            submitButton.setAttribute('data-loading', 'true');
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            submitLabel.textContent = 'Memproses masuk...';
        });

        window.addEventListener('pageshow', function () {
            isSubmitting = false;
            submitButton.removeAttribute('data-loading');
            submitButton.removeAttribute('aria-busy');
            updateRoleState();
        });

        updateRoleState();
    });
})(document);
