(function (document) {
    'use strict';

    function closeMenu(menu, returnFocus) {
        if (!menu.open) {
            return;
        }

        menu.open = false;

        if (returnFocus) {
            menu.querySelector('summary')?.focus();
        }
    }

    document.addEventListener('click', function (event) {
        const selectedItem = event.target.closest('[data-master-action-menu] [role="menuitem"]');

        if (selectedItem) {
            const selectedMenu = selectedItem.closest('[data-master-action-menu]');

            if (selectedMenu) {
                closeMenu(selectedMenu, false);
            }
        }

        document.querySelectorAll('[data-master-action-menu][open]').forEach(function (menu) {
            if (!menu.contains(event.target)) {
                closeMenu(menu, false);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        const openedMenu = document.querySelector('[data-master-action-menu][open]');

        if (openedMenu) {
            closeMenu(openedMenu, true);
        }
    });

    document.querySelectorAll('[data-master-action-menu]').forEach(function (menu) {
        const toggle = menu.querySelector('summary');

        menu.addEventListener('toggle', function () {
            toggle?.setAttribute('aria-expanded', menu.open ? 'true' : 'false');

            if (!menu.open) {
                return;
            }

            document.querySelectorAll('[data-master-action-menu][open]').forEach(function (otherMenu) {
                if (otherMenu !== menu) {
                    closeMenu(otherMenu, false);
                }
            });
        });
    });
})(document);
