(function (window, document) {
    'use strict';

    const storageKey = 'store-app.sidebar-collapsed';

    function updateSidebar(sidebar, trigger, isCollapsed) {
        sidebar.toggleAttribute('data-collapsed', isCollapsed);
        trigger.setAttribute('aria-expanded', String(!isCollapsed));
        trigger.setAttribute('aria-label', isCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar');
        trigger.setAttribute('data-tooltip', isCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const trigger = document.querySelector('[data-sidebar-toggle]');

        if (!sidebar || !trigger) {
            return;
        }

        const helpers = window.StoreApp && window.StoreApp.helpers;
        const savedPreference = helpers ? helpers.storage.get(storageKey) : null;

        updateSidebar(sidebar, trigger, savedPreference === 'true');

        trigger.addEventListener('click', function () {
            const nextState = !sidebar.hasAttribute('data-collapsed');

            updateSidebar(sidebar, trigger, nextState);

            if (helpers) {
                helpers.storage.set(storageKey, String(nextState));
            }

            document.dispatchEvent(new CustomEvent('store-app:sidebar-changed', {
                detail: { collapsed: nextState },
            }));
        });
    });
})(window, document);
