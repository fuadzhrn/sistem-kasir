<form
    class="cashier-dashboard__filters card"
    action="{{ route('dashboard.cashier') }}"
    method="GET"
    data-cashier-dashboard-filter
>
    <div class="form-group cashier-dashboard__search">
        <label class="form-label" for="cashier-dashboard-search">Cari Nomor Nota</label>
        <input
            class="form-control"
            id="cashier-dashboard-search"
            name="search"
            type="search"
            maxlength="100"
            value="{{ $dashboard['filters']['search'] ?? '' }}"
            placeholder="Contoh: INV-20260726"
            data-cashier-dashboard-search
        >
    </div>
    <div class="form-group">
        <label class="form-label" for="cashier-dashboard-date-from">Tanggal Mulai</label>
        <input
            class="form-control"
            id="cashier-dashboard-date-from"
            name="date_from"
            type="date"
            max="{{ now()->toDateString() }}"
            value="{{ $dashboard['filters']['date_from'] ?? '' }}"
        >
    </div>
    <div class="form-group">
        <label class="form-label" for="cashier-dashboard-date-to">Tanggal Selesai</label>
        <input
            class="form-control"
            id="cashier-dashboard-date-to"
            name="date_to"
            type="date"
            max="{{ now()->toDateString() }}"
            value="{{ $dashboard['filters']['date_to'] ?? '' }}"
        >
    </div>
    <div class="form-group">
        <label class="form-label" for="cashier-dashboard-status">Status</label>
        <select class="form-control" id="cashier-dashboard-status" name="status">
            <option value="">Semua Status</option>
            <option value="completed" @selected(($dashboard['filters']['status'] ?? null) === 'completed')>Selesai</option>
            <option value="void_requested" @selected(($dashboard['filters']['status'] ?? null) === 'void_requested')>Menunggu Pembatalan</option>
            <option value="voided" @selected(($dashboard['filters']['status'] ?? null) === 'voided')>Dibatalkan</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="cashier-dashboard-per-page">Per Halaman</label>
        <select class="form-control" id="cashier-dashboard-per-page" name="per_page">
            @foreach ([10, 15, 25] as $perPage)
                <option value="{{ $perPage }}" @selected((int) ($dashboard['filters']['per_page'] ?? 10) === $perPage)>
                    {{ $perPage }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="cashier-dashboard__filter-actions">
        <button class="btn btn-primary" type="submit" data-filter-submit>Terapkan Filter</button>
        <a class="btn btn-ghost" href="{{ route('dashboard.cashier') }}" data-filter-reset>Reset</a>
    </div>
</form>
