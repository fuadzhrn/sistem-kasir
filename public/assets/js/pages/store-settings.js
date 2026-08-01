(function (window, document) {
    'use strict';

    function digitsOnly(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function formatRupiahInput(input) {
        const digits = digitsOnly(input.value);
        input.value = digits === ''
            ? ''
            : new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(Number(digits));
    }

    function updateTextPreview(root, sourceName, fallback) {
        const source = root.querySelector('[data-preview-source="' + sourceName + '"]');
        const output = root.querySelector('[data-preview-output="' + sourceName + '"]');

        if (!source || !output) {
            return;
        }

        const value = source.value.trim();
        output.textContent = value || fallback || '';
        output.hidden = output.textContent === '';
    }

    function updateInvoicePreview(root) {
        const format = root.querySelector('[data-number-format]');
        const prefix = root.querySelector('[data-number-prefix]');
        const separator = root.querySelector('[data-number-separator]');
        const digits = root.querySelector('[data-sequence-digits]');
        const preview = root.querySelector('[data-number-preview]');
        const receiptPreview = root.querySelector('[data-preview-invoice]');

        if (!format || !prefix || !separator || !digits || !preview) {
            return;
        }

        const usesSlash = format.value.endsWith('_slash');
        const usesPrefix = format.value.startsWith('prefix_');
        const safeSeparator = usesSlash ? '/' : '-';
        const safePrefix = prefix.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);
        const date = root.dataset.previewDate || '20260731';
        const sequence = '1'.padStart(Number(digits.value) || 4, '0');
        const parts = ['UTM', date, sequence];

        prefix.value = safePrefix;
        separator.value = safeSeparator;

        if (usesPrefix) {
            parts.unshift(safePrefix || 'INV');
        }

        const value = parts.join(safeSeparator);
        preview.textContent = value;

        if (receiptPreview) {
            receiptPreview.textContent = value;
        }
    }

    function updateReceiptPreview(root) {
        updateTextPreview(root, 'store-name', 'Toko');
        updateTextPreview(root, 'store-address', '');
        updateTextPreview(root, 'store-phone', '');
        updateTextPreview(root, 'additional', '');
        updateTextPreview(root, 'footer', 'Terima kasih telah berbelanja.');

        const phoneOutput = root.querySelector('[data-preview-output="store-phone"]');
        const phoneSource = root.querySelector('[data-preview-source="store-phone"]');

        if (phoneOutput && phoneSource && phoneSource.value.trim() !== '') {
            phoneOutput.textContent = 'Telp. ' + phoneSource.value.trim();
            phoneOutput.hidden = false;
        }

        const width = root.querySelector('[data-preview-source="paper-width"]:checked');
        const widthOutput = root.querySelector('[data-preview-width]');

        if (width && widthOutput) {
            widthOutput.textContent = width.value + ' mm';
        }

        const logoToggle = root.querySelector('[data-preview-toggle="show_logo"]');
        const logo = root.querySelector('[data-preview-logo]');

        if (logo && logoToggle) {
            logo.hidden = !logoToggle.checked || !logo.querySelector('img');
        }

        updateInvoicePreview(root);
    }

    function initializeLogoPreview(root) {
        const input = root.querySelector('[data-logo-input]');
        const preview = root.querySelector('[data-logo-preview]');
        const empty = root.querySelector('[data-logo-empty]');
        const fileName = root.querySelector('[data-logo-file-name]');

        if (!input || !preview) {
            return;
        }

        const originalSource = preview.getAttribute('src') || '';
        const originalHidden = preview.hidden;
        const originalAlt = preview.getAttribute('alt') || 'Logo toko';
        const originalEmptyHidden = empty?.hidden ?? false;
        let temporaryUrl = '';

        function revokeTemporaryUrl() {
            if (temporaryUrl) {
                URL.revokeObjectURL(temporaryUrl);
                temporaryUrl = '';
            }
        }

        function restoreCurrentLogo() {
            revokeTemporaryUrl();
            preview.src = originalSource;
            preview.alt = originalAlt;
            preview.hidden = originalHidden;

            if (empty) {
                empty.hidden = originalEmptyHidden;
            }
        }

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];

            if (fileName) {
                fileName.textContent = file ? file.name : 'Belum ada file baru dipilih.';
            }

            if (!file) {
                restoreCurrentLogo();
                return;
            }

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                restoreCurrentLogo();
                return;
            }

            revokeTemporaryUrl();
            temporaryUrl = URL.createObjectURL(file);
            preview.src = temporaryUrl;
            preview.alt = 'Pratinjau logo baru: ' + file.name;
            preview.hidden = false;

            if (empty) {
                empty.hidden = true;
            }
        });

        window.addEventListener('pagehide', revokeTemporaryUrl, { once: true });
    }

    function setSubmitting(form) {
        if (form.dataset.submitting === 'true') {
            return false;
        }

        form.dataset.submitting = 'true';
        const button = form.querySelector('[data-settings-submit], button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Menyimpan...';
        }

        return true;
    }

    function resetSubmittingState(root) {
        root.querySelectorAll('[data-settings-form], [data-logo-delete-form]').forEach(function (form) {
            delete form.dataset.submitting;
            delete form.dataset.confirmed;
            const button = form.querySelector('[data-settings-submit], button[type="submit"]');

            if (!button) {
                return;
            }

            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.textContent = button.dataset.defaultLabel || 'Simpan Pengaturan';
        });
    }

    function initializeForms(root) {
        root.querySelectorAll('[data-settings-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!setSubmitting(form)) {
                    event.preventDefault();
                }
            });
        });

        const deleteForm = root.querySelector('[data-logo-delete-form]');

        deleteForm?.addEventListener('submit', async function (event) {
            if (deleteForm.dataset.confirmed === 'true') {
                if (!setSubmitting(deleteForm)) {
                    event.preventDefault();
                }

                return;
            }

            event.preventDefault();
            const confirmed = await window.StoreApp?.confirm?.(
                'Hapus logo toko? Struk tetap dapat dicetak tanpa logo.',
            );

            if (confirmed) {
                deleteForm.dataset.confirmed = 'true';
                deleteForm.requestSubmit();
            }
        });

        window.addEventListener('pageshow', function () {
            resetSubmittingState(root);
        });
    }

    function initializeNavigation(root) {
        root.querySelectorAll('[data-settings-navigation] a').forEach(function (link) {
            link.addEventListener('click', function () {
                root.querySelectorAll('[data-settings-navigation] a').forEach(function (item) {
                    item.classList.remove('is-active');
                });
                link.classList.add('is-active');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-store-settings]');

        if (!root) {
            return;
        }

        root.querySelectorAll('[data-rupiah-input]').forEach(function (input) {
            formatRupiahInput(input);
            input.addEventListener('input', function () {
                formatRupiahInput(input);
            });
        });

        root.querySelectorAll(
            '[data-preview-source], [data-preview-toggle], [data-number-format], [data-number-prefix], [data-number-separator], [data-sequence-digits]',
        ).forEach(function (field) {
            field.addEventListener('input', function () {
                updateReceiptPreview(root);
            });
            field.addEventListener('change', function () {
                updateReceiptPreview(root);
            });
        });

        initializeLogoPreview(root);
        initializeForms(root);
        initializeNavigation(root);
        updateReceiptPreview(root);
    });
}(window, document));
