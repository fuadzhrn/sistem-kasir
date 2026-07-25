(function (window, document) {
    'use strict';

    function openConfiguredModal(id, formSelector, trigger, nameSelector, valueSelector) {
        const modal = document.getElementById(id);

        if (!modal || !window.StoreApp || !window.StoreApp.modal) {
            return;
        }

        const form = modal.querySelector(formSelector);
        const name = modal.querySelector(nameSelector);
        const value = valueSelector ? modal.querySelector(valueSelector) : null;

        if (!form) {
            return;
        }

        form.action = trigger.dataset.action || '';

        if (name) {
            name.textContent = trigger.dataset.name || 'kategori ini';
        }

        if (value) {
            value.value = trigger.dataset.nextStatus || '';
        }

        window.StoreApp.modal.open(modal);
    }

    document.addEventListener('click', function (event) {
        const statusTrigger = event.target.closest('[data-expense-category-status]');
        const deleteTrigger = event.target.closest('[data-expense-category-delete]');

        if (statusTrigger) {
            openConfiguredModal(
                'expense-category-status-modal',
                '[data-expense-category-status-form]',
                statusTrigger,
                '[data-expense-category-status-name]',
                '[data-expense-category-status-value]'
            );
        } else if (deleteTrigger) {
            openConfiguredModal(
                'expense-category-delete-modal',
                '[data-expense-category-delete-form]',
                deleteTrigger,
                '[data-expense-category-delete-name]'
            );
        }
    });
})(window, document);
