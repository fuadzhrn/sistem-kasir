export async function fetchDashboardData(endpoint, parameters, signal) {
    const url = new URL(endpoint, window.location.origin);
    url.search = parameters.toString();

    const response = await fetch(url, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal,
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok || !payload?.success || !payload?.data) {
        const validationMessage = payload?.errors
            ? Object.values(payload.errors).flat()[0]
            : null;
        throw new Error(
            validationMessage
            || payload?.message
            || 'Data dashboard cabang belum dapat dimuat.',
        );
    }

    return payload.data;
}
