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

    function setModalText(modal, selector, value, fallback = '—') {
        const output = modal.querySelector(selector);

        if (output) {
            output.textContent = value || fallback;
        }
    }

    function configureModal(modalId, formSelector, trigger) {
        const modal = document.getElementById(modalId);

        if (!modal || !window.StoreApp || !window.StoreApp.modal) {
            return;
        }

        const form = modal.querySelector(formSelector);

        if (!form) {
            return;
        }

        const previousAction = form.action;
        form.action = trigger.dataset.action || '';
        setModalText(modal, '[data-expense-modal-description]', trigger.dataset.description, 'Pengeluaran ini');
        setModalText(modal, '[data-expense-modal-amount]', trigger.dataset.amount, 'Rp0');
        setModalText(modal, '[data-expense-modal-branch]', trigger.dataset.branch);
        setModalText(modal, '[data-expense-modal-category]', trigger.dataset.category);
        setModalText(modal, '[data-expense-modal-date]', trigger.dataset.date);
        setModalText(modal, '[data-expense-modal-creator]', trigger.dataset.creator);
        setModalText(modal, '[data-expense-modal-proof]', trigger.dataset.proof, 'Tidak ada');

        const rejectionReason = form.querySelector('[name="rejection_reason"]');

        if (
            rejectionReason
            && previousAction !== form.action
            && !rejectionReason.classList.contains('is-invalid')
        ) {
            rejectionReason.value = '';
        }

        window.StoreApp.modal.open(modal);
    }

    function previewProof(input) {
        const wrapper = document.querySelector('[data-expense-proof-preview]');
        const image = document.querySelector('[data-expense-proof-preview-image]');
        const fileName = document.querySelector('[data-expense-proof-name]');
        const file = input.files && input.files[0];

        if (fileName) {
            fileName.textContent = file ? file.name : 'Belum ada file baru dipilih.';
        }

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

            if (fileName) {
                fileName.textContent = 'Belum ada file baru dipilih.';
            }

            window.StoreApp.showToast?.({
                type: 'danger',
                title: 'Bukti tidak valid',
                message: 'Gunakan JPG, JPEG, PNG, atau WEBP dengan ukuran maksimal 3 MB.',
            });

            return;
        }

        const previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        image.addEventListener('load', function releasePreviewUrl() {
            URL.revokeObjectURL(previewUrl);
        }, { once: true });
        wrapper.hidden = false;
    }

    function bindSubmitGuard(form, loadingText, beforeSubmit = null) {
        const submitButton = form.querySelector(
            '[data-submit-button], [data-expense-action-submit], button[type="submit"]',
        );

        if (!submitButton) {
            return;
        }

        submitButton.dataset.defaultLabel = submitButton.textContent.trim();
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();

                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            if (typeof beforeSubmit === 'function') {
                beforeSubmit();
            }

            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            submitButton.textContent = loadingText;
            submitButton.dataset.loading = 'true';
        });
    }

    function resetSubmitGuards() {
        document.querySelectorAll('form[data-submitting="true"]').forEach(function (form) {
            form.dataset.submitting = 'false';
            form.setAttribute('aria-busy', 'false');
        });
        document.querySelectorAll('button[data-default-label]').forEach(function (button) {
            button.disabled = false;
            button.textContent = button.dataset.defaultLabel;
            delete button.dataset.loading;
        });
    }

    function initializeFilterPanel() {
        const root = document.querySelector('[data-expense-filters]');
        const toggleButton = document.querySelector('[data-expense-filter-toggle]');
        const closeButton = root?.querySelector('[data-expense-filter-close]');
        const form = root?.querySelector('[data-expense-filter-form]');
        const submitButton = root?.querySelector('[data-expense-filter-submit]');
        const mobileMedia = window.matchMedia('(max-width: 768px)');

        if (!root || !form) {
            return;
        }

        function setOpen(open, restoreFocus = true) {
            root.classList.toggle('is-open', open);
            toggleButton?.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.requestAnimationFrame(function () {
                    form.querySelector('input:not([readonly]), select:not([disabled])')?.focus();
                });
            } else if (restoreFocus) {
                toggleButton?.focus();
            }
        }

        root.classList.add('is-mobile-ready');
        toggleButton?.addEventListener('click', function () {
            setOpen(!root.classList.contains('is-open'));
        });
        closeButton?.addEventListener('click', function () {
            setOpen(false);
        });
        document.addEventListener('keydown', function (event) {
            if (
                event.key === 'Escape'
                && mobileMedia.matches
                && root.classList.contains('is-open')
            ) {
                event.preventDefault();
                setOpen(false);
            }
        });
        form.addEventListener('submit', function () {
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Menerapkan...';
            }
        });
    }

    function closeActionMenu(menu, restoreFocus) {
        if (!menu?.open) {
            return;
        }

        menu.open = false;

        if (restoreFocus) {
            menu.querySelector('summary')?.focus();
        }
    }

    function initializeActionMenus() {
        document.querySelectorAll('[data-expense-action-menu]').forEach(function (menu) {
            const toggle = menu.querySelector('summary');

            menu.addEventListener('toggle', function () {
                toggle?.setAttribute('aria-expanded', menu.open ? 'true' : 'false');

                if (!menu.open) {
                    return;
                }

                document.querySelectorAll('[data-expense-action-menu][open]').forEach(function (other) {
                    if (other !== menu) {
                        closeActionMenu(other, false);
                    }
                });
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeActionMenu(
                    document.querySelector('[data-expense-action-menu][open]'),
                    true,
                );
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeFilterPanel();
        initializeActionMenus();

        document.querySelectorAll('[data-expense-amount]').forEach(function (input) {
            formatAmount(input);
            input.addEventListener('input', function () {
                formatAmount(input);
            });
        });

        document.querySelectorAll('[data-expense-form]').forEach(function (form) {
            bindSubmitGuard(form, 'Menyimpan...', function () {
                const amountInput = form.querySelector('[data-expense-amount]');

                if (amountInput) {
                    amountInput.value = digitsOnly(amountInput.value);
                }
            });
        });

        document.querySelectorAll('[data-expense-approve-form]').forEach(function (form) {
            bindSubmitGuard(form, 'Memproses...');
        });
        document.querySelectorAll('[data-expense-reject-form]').forEach(function (form) {
            bindSubmitGuard(form, 'Memproses...');
        });
        document.querySelectorAll('[data-expense-remove-proof-form]').forEach(function (form) {
            bindSubmitGuard(form, 'Memproses...');
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
                configureModal(
                    'expense-approve-modal',
                    '[data-expense-approve-form]',
                    approveTrigger,
                );
            } else if (rejectTrigger) {
                configureModal(
                    'expense-reject-modal',
                    '[data-expense-reject-form]',
                    rejectTrigger,
                );
            } else if (removeProofTrigger) {
                configureModal(
                    'expense-remove-proof-modal',
                    '[data-expense-remove-proof-form]',
                    removeProofTrigger,
                );
            }

            document.querySelectorAll('[data-expense-action-menu][open]').forEach(function (menu) {
                if (
                    approveTrigger
                    || rejectTrigger
                    || removeProofTrigger
                    || !menu.contains(event.target)
                ) {
                    closeActionMenu(menu, false);
                }
            });
        });
    });

    window.addEventListener('pageshow', resetSubmitGuards);
})(window, document);
