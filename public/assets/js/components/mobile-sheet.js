const focusableSelector = [
    'a[href]',
    'button:not(:disabled)',
    'input:not(:disabled)',
    'select:not(:disabled)',
    'textarea:not(:disabled)',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export function initializeMobileSheet({
    root,
    stateController,
    selectors,
    breakpoint = '(max-width: 768px)',
}) {
    const sheet = root?.querySelector(selectors.sheet);
    const dialog = root?.querySelector(selectors.dialog);
    const openButton = root?.querySelector(selectors.open);
    const overlay = root?.querySelector(selectors.overlay);
    const closeButtons = Array.from(root?.querySelectorAll(selectors.close) || []);
    const compactLayout = window.matchMedia(breakpoint);
    let isOpen = false;

    if (!sheet || !dialog || !openButton || !overlay) {
        return { close: () => false };
    }

    function close(reason = 'close') {
        if (!isOpen) {
            return false;
        }

        isOpen = false;
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        openButton.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('is-modal-open');

        if (!['apply', 'reset'].includes(reason)) {
            stateController?.restore?.();
        }

        if (reason !== 'viewport') {
            openButton.focus();
        }

        return true;
    }

    function syncMode() {
        if (compactLayout.matches) {
            sheet.setAttribute('aria-hidden', String(!isOpen));
            dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');

            return;
        }

        close('viewport');
        sheet.removeAttribute('aria-hidden');
        dialog.removeAttribute('role');
        dialog.removeAttribute('aria-modal');
    }

    function open() {
        if (!compactLayout.matches || isOpen) {
            return false;
        }

        stateController?.restore?.();
        isOpen = true;
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        openButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('is-modal-open');

        window.requestAnimationFrame(() => {
            (dialog.querySelector(focusableSelector) || dialog).focus();
        });

        return true;
    }

    function trapFocus(event) {
        if (!isOpen || event.key !== 'Tab') {
            return;
        }

        const focusableElements = Array.from(dialog.querySelectorAll(focusableSelector))
            .filter((element) => !element.hidden);

        if (focusableElements.length === 0) {
            event.preventDefault();
            dialog.focus();

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

    openButton.addEventListener('click', open);
    overlay.addEventListener('click', () => close('overlay'));
    closeButtons.forEach((button) => {
        button.addEventListener('click', () => close('cancel'));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            event.preventDefault();
            close('escape');

            return;
        }

        trapFocus(event);
    });

    if (typeof compactLayout.addEventListener === 'function') {
        compactLayout.addEventListener('change', syncMode);
    } else {
        compactLayout.addListener(syncMode);
    }

    syncMode();

    return { close };
}
