(function (window, document) {
    'use strict';

    function initializeDateRange(form) {
        const dateFrom = form.querySelector('[name="date_from"]');
        const dateTo = form.querySelector('[name="date_to"]');

        if (!dateFrom || !dateTo) {
            return;
        }

        function updateMinimumDate() {
            dateTo.min = dateFrom.value;
        }

        dateFrom.addEventListener('change', updateMinimumDate);
        updateMinimumDate();
    }

    function initializeFilterPanel(form) {
        const toggleButton = document.querySelector('[data-activity-filter-toggle]');
        const mobileMedia = window.matchMedia('(max-width: 768px)');

        function setOpen(open, restoreFocus = true) {
            form.classList.toggle('is-open', open);
            toggleButton?.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.requestAnimationFrame(function focusFirstField() {
                    form.querySelector('input:not([disabled]), select:not([disabled])')?.focus();
                });
            } else if (restoreFocus) {
                toggleButton?.focus();
            }
        }

        form.classList.add('is-mobile-ready');
        toggleButton?.addEventListener('click', function toggleFilterPanel() {
            setOpen(!form.classList.contains('is-open'));
        });
        form.querySelectorAll('[data-activity-filter-close]').forEach(function bindClose(button) {
            button.addEventListener('click', function closeFilterPanel() {
                setOpen(false);
            });
        });
        document.addEventListener('keydown', function closeWithEscape(event) {
            if (event.key === 'Escape' && mobileMedia.matches && form.classList.contains('is-open')) {
                event.preventDefault();
                setOpen(false);
            }
        });
    }

    function initializeSubmitGuard(form) {
        const submitButton = form.querySelector('[data-activity-filter-submit]');

        if (!submitButton) {
            return;
        }

        submitButton.dataset.defaultLabel = submitButton.textContent.trim();
        form.addEventListener('submit', function guardSubmit(event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();

                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            submitButton.textContent = 'Memuat…';
        });
    }

    function resetSubmitGuard() {
        document.querySelectorAll('[data-activity-filters]').forEach(function resetForm(form) {
            form.dataset.submitting = 'false';
            form.setAttribute('aria-busy', 'false');

            const submitButton = form.querySelector('[data-activity-filter-submit]');

            if (submitButton?.dataset.defaultLabel) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.defaultLabel;
            }
        });
    }

    function initializeTechnicalSections() {
        document.querySelectorAll('[data-activity-technical]').forEach(function bindSection(section) {
            const summary = section.querySelector('summary');
            const icon = section.querySelector('[data-activity-technical-icon]');

            function updateExpandedState() {
                summary?.setAttribute('aria-expanded', section.open ? 'true' : 'false');

                if (icon) {
                    icon.textContent = section.open ? '−' : '+';
                }
            }

            section.addEventListener('toggle', updateExpandedState);
            updateExpandedState();
        });
    }

    document.addEventListener('DOMContentLoaded', function initializeActivitiesPage() {
        const form = document.querySelector('[data-activity-filters]');

        if (form) {
            initializeDateRange(form);
            initializeFilterPanel(form);
            initializeSubmitGuard(form);
        }

        initializeTechnicalSections();
    });

    window.addEventListener('pageshow', resetSubmitGuard);
})(window, document);
