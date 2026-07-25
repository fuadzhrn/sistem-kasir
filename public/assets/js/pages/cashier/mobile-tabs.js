export function initializeMobileTabs(root) {
    const tabs = Array.from(root.querySelectorAll('[data-cashier-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-cashier-panel]'));

    function activate(name, focusPanel = false) {
        tabs.forEach(function (tab) {
            const active = tab.dataset.cashierTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.cashierPanel === name);
        });

        if (focusPanel && name === 'cart') {
            root.querySelector('[data-cart-heading]').focus();
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

    root.querySelector('[data-mobile-cart-bar]').addEventListener('click', function () {
        activate('cart', true);
    });

    return { activate };
}
