const page = document.querySelector('[data-report-page]');

if (page) {
    const form = page.querySelector('[data-report-filter]');
    const period = form?.querySelector('[data-report-period]');
    const customPeriod = form?.querySelector('[data-report-custom]');
    const filterToggle = page.querySelector('[data-report-filter-toggle]');
    const mobileMedia = window.matchMedia('(max-width: 768px)');

    function updateCustomPeriod() {
        if (!period || !customPeriod || !form) {
            return;
        }

        const active = period.value === 'custom';
        const dateFrom = form.elements.namedItem('date_from');
        const dateTo = form.elements.namedItem('date_to');

        customPeriod.hidden = !active;

        if (dateFrom instanceof HTMLInputElement) {
            dateFrom.required = active;
        }

        if (dateTo instanceof HTMLInputElement) {
            dateTo.required = active;
        }
    }

    function setFilterOpen(open, restoreFocus = true) {
        if (!form) {
            return;
        }

        form.classList.toggle('is-open', open);
        filterToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.requestAnimationFrame(function focusFirstFilter() {
                form.querySelector('select:not([disabled]), input:not([disabled])')?.focus();
            });
        } else if (restoreFocus) {
            filterToggle?.focus();
        }
    }

    function initializeFilterPanel() {
        if (!form) {
            return;
        }

        form.classList.add('is-mobile-ready');
        filterToggle?.addEventListener('click', function toggleFilterPanel() {
            setFilterOpen(!form.classList.contains('is-open'));
        });

        form.querySelectorAll('[data-report-filter-close]').forEach(function bindClose(button) {
            button.addEventListener('click', function closeFilterPanel() {
                setFilterOpen(false);
            });
        });

        document.addEventListener('keydown', function closeFilterWithEscape(event) {
            if (event.key === 'Escape' && mobileMedia.matches && form.classList.contains('is-open')) {
                event.preventDefault();
                setFilterOpen(false);
            }
        });
    }

    function initializeSubmitGuard() {
        if (!form) {
            return;
        }

        const submitButton = form.querySelector('[data-report-submit]');

        if (!submitButton) {
            return;
        }

        submitButton.dataset.defaultLabel = submitButton.textContent.trim();
        form.addEventListener('submit', function guardReportSubmit(event) {
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
        if (!form) {
            return;
        }

        form.dataset.submitting = 'false';
        form.setAttribute('aria-busy', 'false');

        const submitButton = form.querySelector('[data-report-submit]');

        if (submitButton?.dataset.defaultLabel) {
            submitButton.disabled = false;
            submitButton.textContent = submitButton.dataset.defaultLabel;
        }
    }

    function updateScrollableTable(wrapper) {
        const maxScrollLeft = wrapper.scrollWidth - wrapper.clientWidth;
        const scrollable = maxScrollLeft > 1;

        wrapper.classList.toggle('is-scrollable', scrollable);

        if (!scrollable || wrapper.scrollLeft <= 1) {
            wrapper.dataset.scrollPosition = 'start';
        } else if (wrapper.scrollLeft >= maxScrollLeft - 1) {
            wrapper.dataset.scrollPosition = 'end';
        } else {
            wrapper.dataset.scrollPosition = 'middle';
        }
    }

    function initializeScrollableTables() {
        const wrappers = page.querySelectorAll('[data-report-table-scroll]');

        if (wrappers.length === 0) {
            return;
        }

        wrappers.forEach(function bindScrollableTable(wrapper) {
            wrapper.addEventListener('scroll', function updateScrollPosition() {
                updateScrollableTable(wrapper);
            }, { passive: true });
            updateScrollableTable(wrapper);
        });

        let resizeFrame = null;
        window.addEventListener('resize', function updateTablesAfterResize() {
            if (resizeFrame !== null) {
                window.cancelAnimationFrame(resizeFrame);
            }

            resizeFrame = window.requestAnimationFrame(function refreshTableWidths() {
                wrappers.forEach(updateScrollableTable);
                resizeFrame = null;
            });
        });
    }

    period?.addEventListener('change', updateCustomPeriod);
    updateCustomPeriod();
    initializeFilterPanel();
    initializeSubmitGuard();
    initializeScrollableTables();
    window.addEventListener('pageshow', resetSubmitGuard);
}
