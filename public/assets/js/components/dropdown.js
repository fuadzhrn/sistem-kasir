(function (document) {
    'use strict';

    function closeDropdown(trigger, returnFocus) {
        const targetId = trigger.getAttribute('aria-controls');
        const menu = targetId ? document.getElementById(targetId) : null;

        trigger.setAttribute('aria-expanded', 'false');

        if (menu) {
            menu.hidden = true;
        }

        if (returnFocus) {
            trigger.focus();
        }
    }

    function closeAll(exceptTrigger) {
        document.querySelectorAll('[data-dropdown-toggle][aria-expanded="true"]').forEach(function (trigger) {
            if (trigger !== exceptTrigger) {
                closeDropdown(trigger, false);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dropdown-toggle]');

            if (trigger) {
                const targetId = trigger.getAttribute('aria-controls');
                const menu = targetId ? document.getElementById(targetId) : null;
                const willOpen = trigger.getAttribute('aria-expanded') !== 'true';

                closeAll(trigger);
                trigger.setAttribute('aria-expanded', String(willOpen));

                if (menu) {
                    menu.hidden = !willOpen;
                }

                return;
            }

            if (!event.target.closest('[data-dropdown-menu]')) {
                closeAll();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            const openTrigger = document.querySelector('[data-dropdown-toggle][aria-expanded="true"]');

            if (openTrigger) {
                closeDropdown(openTrigger, true);
            }
        });
    });
})(document);
