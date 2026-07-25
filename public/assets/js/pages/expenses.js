(function (window, document) {
    'use strict';

    const rupiahNumber = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    function digitsOnly(value) {
        return String(value || '').replace(/[^\d]/g, '');
    }

    function formatAmount(input) {
        const digits = digitsOnly(input.value);

        input.value = digits === '' ? '' : rupiahNumber.format(BigInt(digits));
    }

    function configureModal(modalId, formSelector, trigger, descriptionSelector) {
        const modal = document.getElementById(modalId);

        if (!modal || !window.StoreApp || !window.StoreApp.modal) {
            return;
        }

        const form = modal.querySelector(formSelector);
        const description = modal.querySelector(descriptionSelector);
        const amount = modal.querySelector('[data-expense-modal-amount]');

        if (!form) {
            return;
        }

        form.action = trigger.dataset.action || '';

        if (description) {
            description.textContent = trigger.dataset.description || 'pengeluaran ini';
        }

        if (amount) {
            amount.textContent = trigger.dataset.amount || 'Rp0';
        }

        window.StoreApp.modal.open(modal);
    }

    function previewProof(input) {
        const wrapper = document.querySelector('[data-expense-proof-preview]');
        const image = document.querySelector('[data-expense-proof-preview-image]');
        const file = input.files && input.files[0];

        if (!wrapper || !image) {
            return;
        }

        if (!file) {
            wrapper.hidden = true;
            image.removeAttribute('src');

            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!allowedTypes.includes(file.type) || file.size > 3 * 1024 * 1024) {
            input.value = '';
            wrapper.hidden = true;
            window.StoreApp.showToast?.({
                type: 'danger',
                title: 'Bukti tidak valid',
                message: 'Gunakan JPG, JPEG, PNG, atau WEBP dengan ukuran maksimal 3 MB.',
            });

            return;
        }

        image.src = URL.createObjectURL(file);
        image.addEventListener('load', function releasePreviewUrl() {
            URL.revokeObjectURL(image.src);
        }, { once: true });
        wrapper.hidden = false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-expense-amount]').forEach(function (input) {
            formatAmount(input);
            input.addEventListener('input', function () {
                formatAmount(input);
            });
        });

        document.querySelectorAll('[data-expense-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const amountInput = form.querySelector('[data-expense-amount]');
                const submitButton = form.querySelector('[data-submit-button]');

                if (amountInput) {
                    amountInput.value = digitsOnly(amountInput.value);
                }

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        });

        document.querySelectorAll('[data-expense-proof]').forEach(function (input) {
            input.addEventListener('change', function () {
                previewProof(input);
            });
        });

        document.addEventListener('click', function (event) {
            const approveTrigger = event.target.closest('[data-expense-approve]');
            const rejectTrigger = event.target.closest('[data-expense-reject]');
            const removeProofTrigger = event.target.closest('[data-expense-remove-proof]');

            if (approveTrigger) {
                configureModal('expense-approve-modal', '[data-expense-approve-form]', approveTrigger, '[data-expense-modal-description]');
            } else if (rejectTrigger) {
                configureModal('expense-reject-modal', '[data-expense-reject-form]', rejectTrigger, '[data-expense-modal-description]');
            } else if (removeProofTrigger) {
                configureModal('expense-remove-proof-modal', '[data-expense-remove-proof-form]', removeProofTrigger, '[data-unused-description]');
            }
        });
    });
})(window, document);
