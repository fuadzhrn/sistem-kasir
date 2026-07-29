export function initializeMobileTabs(root) {
    const tabs = Array.from(root.querySelectorAll('[data-cashier-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-cashier-panel]'));
    const mobileMedia = window.matchMedia('(max-width: 768px)');
    const summaryAction = root.querySelector('[data-mobile-summary-action]');
    const summaryActionLabel = root.querySelector('[data-mobile-summary-action-label]');
    const paymentSheet = root.querySelector('[data-payment-sheet]');
    const paymentDialog = root.querySelector('[data-payment-dialog]');
    const paymentCloseControls = Array.from(root.querySelectorAll('[data-payment-sheet-close]'));
    let activeName = 'products';
    let paymentOpen = false;

    function setPaymentOpen(open, restoreFocus = true) {
        paymentOpen = Boolean(open && mobileMedia.matches);
        paymentSheet.classList.toggle('is-open', paymentOpen);
        paymentSheet.setAttribute(
            'aria-hidden',
            paymentOpen || !mobileMedia.matches ? 'false' : 'true',
        );
        if (activeName === 'cart') {
            summaryAction.setAttribute('aria-expanded', paymentOpen ? 'true' : 'false');
        } else {
            summaryAction.removeAttribute('aria-expanded');
        }
        document.body.classList.toggle('cashier-payment-open', paymentOpen);

        if (paymentOpen) {
            paymentDialog.setAttribute('role', 'dialog');
            paymentDialog.setAttribute('aria-modal', 'true');
            paymentDialog.focus();
        } else {
            paymentDialog.removeAttribute('role');
            paymentDialog.removeAttribute('aria-modal');

            if (restoreFocus && mobileMedia.matches) {
                summaryAction.focus();
            }
        }
    }

    function activate(name, focusPanel = false) {
        activeName = name;
        tabs.forEach(function (tab) {
            const active = tab.dataset.cashierTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
        });
        panels.forEach(function (panel) {
            const active = panel.dataset.cashierPanel === name;
            panel.classList.toggle('is-active', active);
            panel.hidden = mobileMedia.matches && !active;
        });
        summaryActionLabel.textContent = name === 'cart'
            ? 'Lanjut Pembayaran'
            : 'Lihat Keranjang';
        if (name === 'cart') {
            summaryAction.setAttribute('aria-haspopup', 'dialog');
            summaryAction.setAttribute('aria-controls', 'cashier-payment-sheet');
        } else {
            summaryAction.removeAttribute('aria-haspopup');
            summaryAction.removeAttribute('aria-controls');
            summaryAction.removeAttribute('aria-expanded');
        }

        if (focusPanel && name === 'cart') {
            root.querySelector('[data-cart-heading]').focus();
        }

        if (name === 'products') {
            setPaymentOpen(false, false);
        }
    }

    function syncResponsiveState() {
        panels.forEach(function (panel) {
            panel.hidden = mobileMedia.matches && panel.dataset.cashierPanel !== activeName;
        });

        if (!mobileMedia.matches) {
            setPaymentOpen(false, false);
            paymentSheet.setAttribute('aria-hidden', 'false');
        } else if (!paymentOpen) {
            paymentSheet.setAttribute('aria-hidden', 'true');
        }
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () {
            activate(tab.dataset.cashierTab);
        });
        tab.addEventListener('keydown', function (event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            let targetIndex = index;

            if (event.key === 'ArrowRight') {
                targetIndex = (index + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft') {
                targetIndex = (index - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                targetIndex = 0;
            } else if (event.key === 'End') {
                targetIndex = tabs.length - 1;
            }

            tabs[targetIndex].focus();
            activate(tabs[targetIndex].dataset.cashierTab);
        });
    });

    summaryAction.addEventListener('click', function () {
        if (activeName === 'products') {
            activate('cart', true);

            return;
        }

        setPaymentOpen(true);
    });
    root.querySelector('[data-show-products]').addEventListener('click', function () {
        activate('products');
        tabs.find(function (tab) {
            return tab.dataset.cashierTab === 'products';
        })?.focus();
    });
    paymentCloseControls.forEach(function (control) {
        control.addEventListener('click', function () {
            setPaymentOpen(false);
        });
    });
    paymentDialog.addEventListener('keydown', function (event) {
        if (!paymentOpen) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            setPaymentOpen(false);

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(paymentDialog.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), [href]',
        )).filter(function (element) {
            return !element.hidden;
        });

        if (focusable.length === 0) {
            event.preventDefault();

            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (
            event.shiftKey
            && (document.activeElement === first || document.activeElement === paymentDialog)
        ) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    root.addEventListener('cashier:cart-summary', function (event) {
        if (event.detail?.kinds === 0) {
            setPaymentOpen(false, false);
        }
    });
    root.addEventListener('cashier:payment-success', function () {
        setPaymentOpen(false, false);
    });
    mobileMedia.addEventListener('change', syncResponsiveState);
    syncResponsiveState();

    return {
        activate,
        openPayment: function () {
            setPaymentOpen(true);
        },
    };
}
