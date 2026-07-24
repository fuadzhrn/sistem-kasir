(function (window, document) {
    'use strict';

    window.StoreApp = window.StoreApp || {};

    let activeModal = null;
    let lastFocusedElement = null;

    function getFocusableElements(modal) {
        return Array.from(modal.querySelectorAll(
            'a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return !element.hasAttribute('hidden');
        });
    }

    function resolveModal(target) {
        if (typeof target === 'string') {
            return document.getElementById(target);
        }

        return target;
    }

    function open(target) {
        const modal = resolveModal(target);

        if (!modal) {
            return false;
        }

        lastFocusedElement = document.activeElement;
        activeModal = modal;
        modal.hidden = false;
        document.body.classList.add('is-modal-open');

        const focusableElements = getFocusableElements(modal);
        const dialog = modal.querySelector('[data-modal-dialog]');

        window.requestAnimationFrame(function () {
            (focusableElements[0] || dialog).focus();
        });

        modal.dispatchEvent(new CustomEvent('store-app:modal-opened'));

        return true;
    }

    function close(target, reason) {
        const modal = resolveModal(target) || activeModal;

        if (!modal || modal.hidden) {
            return false;
        }

        modal.hidden = true;
        document.body.classList.remove('is-modal-open');
        activeModal = null;
        modal.dispatchEvent(new CustomEvent('store-app:modal-closed', {
            detail: { reason: reason || 'close' },
        }));

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }

        lastFocusedElement = null;

        return true;
    }

    function trapFocus(event) {
        if (!activeModal || event.key !== 'Tab') {
            return;
        }

        const focusableElements = getFocusableElements(activeModal);

        if (focusableElements.length === 0) {
            event.preventDefault();

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
    }

    window.StoreApp.modal = {
        open: open,
        close: close,
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const openTrigger = event.target.closest('[data-modal-open]');

            if (openTrigger) {
                open(openTrigger.getAttribute('data-modal-open'));

                return;
            }

            const closeTrigger = event.target.closest('[data-modal-close]');

            if (closeTrigger) {
                close(closeTrigger.closest('[data-modal]'), 'cancel');

                return;
            }

            const overlay = event.target.closest('[data-modal-overlay]');

            if (overlay) {
                const modal = overlay.closest('[data-modal]');

                if (modal && modal.getAttribute('data-close-on-overlay') === 'true') {
                    close(modal, 'overlay');
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && activeModal) {
                event.preventDefault();
                close(activeModal, 'escape');
            } else {
                trapFocus(event);
            }
        });
    });
})(window, document);
