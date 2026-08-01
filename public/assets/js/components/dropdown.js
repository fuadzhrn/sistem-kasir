(function (document) {
    'use strict';

    const actionMenuSelector = [
        '[data-ui-action-menu]',
        '[data-master-action-menu]',
        '[data-expense-action-menu]',
    ].join(',');
    const openActionMenuSelector = [
        '[data-ui-action-menu][open]',
        '[data-master-action-menu][open]',
        '[data-expense-action-menu][open]',
    ].join(',');
    const actionMenuItemSelector = [
        '[data-ui-action-menu] [role="menuitem"]',
        '[data-master-action-menu] [role="menuitem"]',
        '[data-expense-action-menu] [role="menuitem"]',
    ].join(',');

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

    function closeActionMenu(menu, returnFocus) {
        if (!menu?.open) {
            return;
        }

        menu.open = false;

        if (returnFocus) {
            menu.querySelector('summary')?.focus();
        }
    }

    function closeAllActionMenus(exceptMenu) {
        document.querySelectorAll(openActionMenuSelector).forEach(function (menu) {
            if (menu !== exceptMenu) {
                closeActionMenu(menu, false);
            }
        });
    }

    function initializeActionMenus() {
        document.querySelectorAll(actionMenuSelector).forEach(function (menu) {
            const toggle = menu.querySelector('summary');

            if (!toggle) {
                return;
            }

            toggle.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
            menu.addEventListener('toggle', function () {
                toggle.setAttribute('aria-expanded', menu.open ? 'true' : 'false');

                if (menu.open) {
                    closeAllActionMenus(menu);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeActionMenus();

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

            const selectedActionItem = event.target.closest(actionMenuItemSelector);
            const selectedActionMenu = selectedActionItem?.closest(actionMenuSelector);

            if (selectedActionMenu) {
                closeActionMenu(selectedActionMenu, false);
            }

            const clickedActionMenu = event.target.closest(actionMenuSelector);
            closeAllActionMenus(clickedActionMenu);

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

            const openActionMenu = document.querySelector(openActionMenuSelector);

            if (openActionMenu) {
                closeActionMenu(openActionMenu, true);
            }
        });
    });
})(document);
