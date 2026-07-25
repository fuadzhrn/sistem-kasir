(function () {
    'use strict';

    const allowedPaperWidths = ['58', '80'];
    const storageKey = 'receipt_paper_width';
    let hasAutoPrinted = false;

    function validatedPaperWidth(value) {
        return allowedPaperWidths.includes(String(value)) ? String(value) : '80';
    }

    function readPaperPreference() {
        try {
            return validatedPaperWidth(window.localStorage.getItem(storageKey));
        } catch (error) {
            return '80';
        }
    }

    function savePaperPreference(value) {
        try {
            window.localStorage.setItem(storageKey, validatedPaperWidth(value));
        } catch (error) {
            // The receipt remains printable when browser storage is unavailable.
        }
    }

    function applyPaperWidth(paper, select, value) {
        const width = validatedPaperWidth(value);
        paper.classList.remove('receipt-paper--58', 'receipt-paper--80');
        paper.classList.add('receipt-paper--' + width);

        if (select) {
            select.value = width;
        }
    }

    function waitForImages(root) {
        const pendingImages = Array.from(root.querySelectorAll('img'))
            .filter(function (image) {
                return !image.complete;
            })
            .map(function (image) {
                return new Promise(function (resolve) {
                    image.addEventListener('load', resolve, { once: true });
                    image.addEventListener('error', resolve, { once: true });
                });
            });

        return Promise.all(pendingImages);
    }

    function waitForFonts() {
        return document.fonts?.ready || Promise.resolve();
    }

    function waitForLayout() {
        return new Promise(function (resolve) {
            window.setTimeout(resolve, 300);
        });
    }

    function invokePrint(status, automatic) {
        if (automatic && hasAutoPrinted) {
            return;
        }

        if (automatic) {
            hasAutoPrinted = true;
        }

        status.textContent = automatic
            ? 'Dialog cetak dibuka otomatis.'
            : 'Membuka kembali dialog cetak…';

        try {
            window.print();
        } catch (error) {
            status.textContent = 'Dialog cetak tidak dapat dibuka. Gunakan Ctrl+P.';
        }
    }

    document.addEventListener('DOMContentLoaded', async function () {
        const paper = document.querySelector('[data-receipt-paper]');

        if (!paper) {
            return;
        }

        const select = document.querySelector('[data-receipt-paper-select]');
        const printButton = document.querySelector('[data-receipt-print-button]');
        const status = document.querySelector('[data-receipt-print-status]');
        applyPaperWidth(paper, select, readPaperPreference());

        select?.addEventListener('change', function () {
            const width = validatedPaperWidth(select.value);
            applyPaperWidth(paper, select, width);
            savePaperPreference(width);
            status.textContent = 'Ukuran kertas diubah menjadi ' + width + ' mm.';
        });

        printButton?.addEventListener('click', function () {
            invokePrint(status, false);
        });

        window.addEventListener('afterprint', function () {
            status.textContent = 'Dialog cetak telah ditutup.';
        });

        if (paper.dataset.receiptAutoPrint === 'true') {
            await Promise.all([waitForFonts(), waitForImages(paper)]);
            await waitForLayout();
            invokePrint(status, true);
        } else {
            status.textContent = 'Struk siap dicetak.';
        }
    });
}());
