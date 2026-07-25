export function initializeBranchSwitcher(root, store) {
    const selector = root.querySelector('[data-branch-selector]');
    const confirmButton = root.querySelector('[data-confirm-branch-change]');
    let pendingBranchId = null;

    if (!selector) {
        return;
    }

    function navigate(branchId) {
        const url = new URL(root.dataset.cashierUrl, window.location.origin);

        if (branchId) {
            url.searchParams.set('branch_id', branchId);
        }

        window.location.assign(url.toString());
    }

    selector.addEventListener('change', function () {
        const branchId = selector.value;

        if (branchId === root.dataset.branchId) {
            return;
        }

        if (store.getItems().length === 0) {
            navigate(branchId);

            return;
        }

        pendingBranchId = branchId;
        selector.value = root.dataset.branchId;
        window.StoreApp.modal.open('cashier-branch-change-modal');
    });

    confirmButton?.addEventListener('click', function () {
        if (pendingBranchId === null) {
            return;
        }

        store.clear();
        navigate(pendingBranchId);
    });
}
