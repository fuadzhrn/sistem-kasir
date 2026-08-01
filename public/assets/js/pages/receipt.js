(function () {
    'use strict';

    const allowedPaperWidths = ['58', '80'];
    const storageKey = 'receipt_paper_width';
    let hasAutoPrinted = false;

    function validatedPaperWidth(value) {
        return allowedPaperWidths.includes(String(value)) ? String(value) : null;
    }

    function readPaperPreference() {
        try {
            return validatedPaperWidth(window.localStorage.getItem(storageKey));
        } catch (error) {
            return null;
        }
    }

    function savePaperPreference(value) {
        try {
            if (value === 'default') {
                window.localStorage.removeItem(storageKey);
            } else {
                const width = validatedPaperWidth(value);

                if (width) {
                    window.localStorage.setItem(storageKey, width);
                }
            }
        } catch (error) {
            // The receipt remains printable when browser storage is unavailable.
        }
    }

    function applyPaperWidth(paper, select, value) {
        const width = validatedPaperWidth(value) || '80';
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
        const closeButton = document.querySelector('[data-receipt-close-button]');
        const toolbar = document.querySelector('[data-receipt-window-name]');
        const status = document.querySelector('[data-receipt-print-status]');
        const isDedicatedReceiptWindow = window.name === toolbar?.dataset.receiptWindowName;
        const serverDefault = validatedPaperWidth(paper.dataset.receiptDefaultWidth) || '80';
        const browserPreference = readPaperPreference();
        applyPaperWidth(paper, null, browserPreference || serverDefault);

        if (select) {
            select.value = browserPreference || 'default';
        }

        select?.addEventListener('change', function () {
            const selected = select.value;
            const width = selected === 'default'
                ? serverDefault
                : (validatedPaperWidth(selected) || serverDefault);
            applyPaperWidth(paper, null, width);
            savePaperPreference(selected);
            select.value = selected === 'default' ? 'default' : width;
            status.textContent = selected === 'default'
                ? 'Menggunakan ukuran default toko ' + width + ' mm.'
                : 'Ukuran kertas diubah menjadi ' + width + ' mm.';
        });

        printButton?.addEventListener('click', function () {
            invokePrint(status, false);
        });

        closeButton?.addEventListener('click', function () {
            window.close();

            window.setTimeout(function () {
                if (!window.closed) {
                    status.textContent = 'Browser tidak mengizinkan tab ditutup otomatis. Silakan tutup tab ini secara manual.';
                }
            }, 150);
        });

        window.addEventListener('afterprint', function () {
            status.textContent = 'Dialog cetak telah ditutup.';

            if (isDedicatedReceiptWindow) {
                window.setTimeout(function () {
                    window.close();
                }, 150);
            }
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
