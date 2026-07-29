(function (document) {
    'use strict';

    function updateToggle(button, input, isVisible) {
        const label = button.querySelector('[data-password-toggle-label]');
        const showIcon = button.querySelector('[data-password-icon-show]');
        const hideIcon = button.querySelector('[data-password-icon-hide]');
        const action = isVisible ? 'Sembunyikan' : 'Tampilkan';

        input.type = isVisible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(isVisible));
        button.setAttribute('aria-label', action + ' kata sandi');

        if (label) {
            label.textContent = action;
        }

        if (showIcon) {
            showIcon.hidden = isVisible;
        }

        if (hideIcon) {
            hideIcon.hidden = !isVisible;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            const targetId = button.getAttribute('data-password-target');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            button.addEventListener('click', function () {
                updateToggle(button, input, input.type === 'password');
                input.focus();
            });
        });
    });
})(document);
