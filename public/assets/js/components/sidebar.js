(function (document) {
    'use strict';

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-sidebar-toggle]');
        const sidebar = document.querySelector('[data-sidebar]');

        if (trigger && sidebar) {
            sidebar.toggleAttribute('data-collapsed');
        }
    });
})(document);
