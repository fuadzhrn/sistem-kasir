(function (window, document) {
    'use strict';

    const storageKey = 'store-app.sidebar-collapsed';
    const mobileNavigationQuery = '(max-width: 1024px)';
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    function updateDesktopSidebar(sidebar, trigger, isCollapsed) {
        sidebar.toggleAttribute('data-collapsed', isCollapsed);

        if (!trigger) {
            return;
        }

        trigger.setAttribute('aria-expanded', String(!isCollapsed));
        trigger.setAttribute('aria-label', isCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar');
        trigger.setAttribute('data-tooltip', isCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar');
    }

    function closeProfileDropdowns(documentElement) {
        documentElement.querySelectorAll('[data-dropdown-toggle][aria-expanded="true"]').forEach(function (trigger) {
            const targetId = trigger.getAttribute('aria-controls');
            const menu = targetId ? documentElement.getElementById(targetId) : null;

            trigger.setAttribute('aria-expanded', 'false');

            if (menu) {
                menu.hidden = true;
            }
        });
    }

    function visibleFocusableElements(container) {
        return Array.from(container.querySelectorAll(focusableSelector)).filter(function (element) {
            return element.getClientRects().length > 0
                && element.getAttribute('aria-hidden') !== 'true';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('[data-sidebar]');
        const collapseTrigger = document.querySelector('[data-sidebar-toggle]');
        const drawerTrigger = document.querySelector('[data-drawer-toggle]');
        const drawerClose = document.querySelector('[data-drawer-close]');
        const drawerOverlay = document.querySelector('[data-drawer-overlay]');
        const appMain = document.querySelector('.app-main');

        if (!sidebar) {
            return;
        }

        const mobileNavigation = window.matchMedia(mobileNavigationQuery);
        const helpers = window.StoreApp && window.StoreApp.helpers;
        const savedPreference = helpers ? helpers.storage.get(storageKey) : null;
        let desktopIsCollapsed = savedPreference === 'true';
        let drawerIsOpen = false;
        let drawerScrollPosition = 0;
        let focusBeforeDrawer = null;

        function setBackgroundInert(isInert) {
            if (!appMain) {
                return;
            }

            if (isInert) {
                appMain.setAttribute('inert', '');
            } else {
                appMain.removeAttribute('inert');
            }
        }

        function updateDrawerAccessibility(isOpen) {
            if (drawerTrigger) {
                drawerTrigger.setAttribute('aria-expanded', String(isOpen));
                drawerTrigger.setAttribute(
                    'aria-label',
                    isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi',
                );
            }

            sidebar.setAttribute('aria-hidden', String(!isOpen));

            if (drawerOverlay) {
                drawerOverlay.setAttribute('aria-hidden', String(!isOpen));
            }
        }

        function closeDrawer(returnFocus) {
            const wasOpen = drawerIsOpen;

            drawerIsOpen = false;
            sidebar.classList.remove('is-open');
            document.body.classList.remove('drawer-open');
            setBackgroundInert(false);

            if (drawerOverlay) {
                drawerOverlay.classList.remove('is-visible');
            }

            if (mobileNavigation.matches) {
                updateDrawerAccessibility(false);
            } else {
                sidebar.removeAttribute('aria-hidden');

                if (drawerOverlay) {
                    drawerOverlay.setAttribute('aria-hidden', 'true');
                }
            }

            if (wasOpen && window.scrollY !== drawerScrollPosition) {
                window.scrollTo({
                    top: drawerScrollPosition,
                    left: 0,
                    behavior: 'auto',
                });
            }

            if (wasOpen && returnFocus && drawerTrigger) {
                window.requestAnimationFrame(function () {
                    const focusTarget = focusBeforeDrawer && focusBeforeDrawer.isConnected
                        ? focusBeforeDrawer
                        : drawerTrigger;

                    focusTarget.focus();
                });
            }
        }

        function openDrawer() {
            if (!mobileNavigation.matches || drawerIsOpen || !drawerTrigger) {
                return;
            }

            closeProfileDropdowns(document);
            drawerIsOpen = true;
            drawerScrollPosition = window.scrollY;
            focusBeforeDrawer = document.activeElement;
            sidebar.removeAttribute('data-collapsed');
            sidebar.classList.add('is-open');
            document.body.classList.add('drawer-open');
            setBackgroundInert(true);
            updateDrawerAccessibility(true);

            if (drawerOverlay) {
                drawerOverlay.classList.add('is-visible');
            }

            const focusDrawer = function () {
                const focusTarget = drawerClose || sidebar;

                focusTarget.focus({ preventScroll: true });
            };

            focusDrawer();
            window.requestAnimationFrame(function () {
                window.setTimeout(focusDrawer, 30);
            });
        }

        function applyResponsiveMode() {
            closeDrawer(false);

            if (mobileNavigation.matches) {
                sidebar.removeAttribute('data-collapsed');
                updateDrawerAccessibility(false);
                return;
            }

            sidebar.removeAttribute('aria-hidden');
            updateDesktopSidebar(sidebar, collapseTrigger, desktopIsCollapsed);
        }

        if (collapseTrigger) {
            collapseTrigger.addEventListener('click', function () {
                if (mobileNavigation.matches) {
                    return;
                }

                const nextState = !sidebar.hasAttribute('data-collapsed');

                desktopIsCollapsed = nextState;
                updateDesktopSidebar(sidebar, collapseTrigger, nextState);

                if (helpers) {
                    helpers.storage.set(storageKey, String(nextState));
                }

                document.dispatchEvent(new CustomEvent('store-app:sidebar-changed', {
                    detail: { collapsed: nextState },
                }));
            });
        }

        if (drawerTrigger) {
            drawerTrigger.addEventListener('click', function () {
                if (drawerIsOpen) {
                    closeDrawer(true);
                } else {
                    openDrawer();
                }
            });
        }

        if (drawerClose) {
            drawerClose.addEventListener('click', function () {
                closeDrawer(true);
            });
        }

        if (drawerOverlay) {
            drawerOverlay.addEventListener('click', function () {
                closeDrawer(true);
            });
        }

        sidebar.addEventListener('click', function (event) {
            const navigationLink = event.target.closest('.sidebar-nav__item[href]');

            if (navigationLink && mobileNavigation.matches) {
                closeDrawer(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!drawerIsOpen) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDrawer(true);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusableElements = visibleFocusableElements(sidebar);

            if (focusableElements.length === 0) {
                event.preventDefault();
                sidebar.focus();
                return;
            }

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        });

        if (typeof mobileNavigation.addEventListener === 'function') {
            mobileNavigation.addEventListener('change', applyResponsiveMode);
        } else {
            mobileNavigation.addListener(applyResponsiveMode);
        }

        window.addEventListener('pageshow', applyResponsiveMode);
        applyResponsiveMode();
    });
})(window, document);
