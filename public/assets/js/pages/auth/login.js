(function (document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-login-form]');
        const submitButton = document.querySelector('[data-login-submit]');

        if (!form || !submitButton) {
            return;
        }

        form.addEventListener('submit', function () {
            submitButton.setAttribute('data-loading', 'true');
            submitButton.setAttribute('aria-busy', 'true');
        });
    });
})(document);
