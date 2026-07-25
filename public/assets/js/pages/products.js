(function (window, document) {
    'use strict';

    const formatRupiah = function (value) {
        const number = Number(value || 0);

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number.isFinite(number) ? number : 0);
    };

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-product-form]');
        const priceModal = document.getElementById('price-confirmation-modal');
        const statusModal = document.getElementById('product-status-modal');
        const removeImageModal = document.getElementById('remove-image-modal');
        const imageInput = document.querySelector('[data-image-input]');
        const imagePreview = document.querySelector('[data-image-preview]');
        let priceConfirmed = false;
        let previewUrl = null;

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function () {
                const file = imageInput.files && imageInput.files[0];

                if (!file) {
                    return;
                }

                if (file.size > 3 * 1024 * 1024) {
                    imageInput.setCustomValidity('Ukuran foto maksimal 3 MB.');
                    imageInput.reportValidity();
                    return;
                }

                imageInput.setCustomValidity('');

                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }

                previewUrl = URL.createObjectURL(file);
                imagePreview.src = previewUrl;
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                const oldSelling = form.dataset.oldSellingPrice;
                const sellingInput = form.querySelector('[name="selling_price"]');
                const purchaseInput = form.querySelector('[name="purchase_price"]');
                const sellingChanged = oldSelling !== undefined
                    && Number(oldSelling) !== Number(sellingInput ? sellingInput.value : 0);
                const purchaseChanged = form.dataset.oldPurchasePrice !== undefined
                    && purchaseInput
                    && Number(form.dataset.oldPurchasePrice) !== Number(purchaseInput.value);

                if (!priceConfirmed && priceModal && (sellingChanged || purchaseChanged) && window.StoreApp) {
                    event.preventDefault();
                    priceModal.querySelector('[data-old-selling]').textContent = formatRupiah(oldSelling);
                    priceModal.querySelector('[data-new-selling]').textContent = formatRupiah(sellingInput.value);

                    if (purchaseInput && priceModal.querySelector('[data-old-purchase]')) {
                        priceModal.querySelector('[data-old-purchase]').textContent = formatRupiah(form.dataset.oldPurchasePrice);
                        priceModal.querySelector('[data-new-purchase]').textContent = formatRupiah(purchaseInput.value);
                    }

                    window.StoreApp.modal.open(priceModal);
                    return;
                }

                const submitButton = form.querySelector('[data-product-submit]');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                }
            });
        }

        const confirmPrice = document.querySelector('[data-price-confirm]');

        if (confirmPrice && form) {
            confirmPrice.addEventListener('click', function () {
                priceConfirmed = true;
                window.StoreApp.modal.close(priceModal);
                form.requestSubmit();
            });
        }

        document.addEventListener('click', function (event) {
            const statusTrigger = event.target.closest('[data-product-status]');
            const removeTrigger = event.target.closest('[data-remove-image]');

            if (statusTrigger && statusModal && window.StoreApp) {
                const activating = statusTrigger.dataset.nextStatus === '1';
                statusModal.querySelector('[data-status-form]').action = statusTrigger.dataset.action;
                statusModal.querySelector('[data-status-value]').value = statusTrigger.dataset.nextStatus;
                statusModal.querySelector('[data-status-title]').textContent = activating ? 'Aktifkan produk' : 'Nonaktifkan produk';
                statusModal.querySelector('[data-status-message]').textContent = activating
                    ? `Aktifkan kembali ${statusTrigger.dataset.name}? Kategori dan satuan harus aktif.`
                    : `Nonaktifkan ${statusTrigger.dataset.name}? Stok, foto, dan histori tidak dihapus.`;
                window.StoreApp.modal.open(statusModal);
            }

            if (removeTrigger && removeImageModal && window.StoreApp) {
                removeImageModal.querySelector('[data-remove-image-form]').action = removeTrigger.dataset.action;
                removeImageModal.querySelector('[data-remove-image-name]').textContent = removeTrigger.dataset.name;
                window.StoreApp.modal.open(removeImageModal);
            }
        });
    });
})(window, document);
