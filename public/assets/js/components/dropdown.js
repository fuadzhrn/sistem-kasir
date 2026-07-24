(function (document) {
    'use strict';

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-dropdown-toggle]');

        if (trigger) {
            const targetId = trigger.getAttribute('aria-controls');
            const dropdown = targetId ? document.getElementById(targetId) : null;

            if (dropdown) {
                dropdown.toggleAttribute('hidden');
                trigger.setAttribute('aria-expanded', String(!dropdown.hasAttribute('hidden')));
            }
        }
    });
})(document);
