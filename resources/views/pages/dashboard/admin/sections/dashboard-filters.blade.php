@php($activePeriod = $dashboard['filters']['period'])

<form
    class="dashboard-filter admin-dashboard__filter card"
    method="GET"
    action="{{ route('dashboard.admin') }}"
    data-dashboard-filter
>
    <div class="form-group">
        <label class="form-label" for="admin-dashboard-period">Periode</label>
        <select class="form-control" id="admin-dashboard-period" name="period" data-period-select>
            <option value="today" @selected($activePeriod === 'today')>Hari Ini</option>
            <option value="this_week" @selected($activePeriod === 'this_week')>Minggu Ini</option>
            <option value="this_month" @selected($activePeriod === 'this_month')>Bulan Ini</option>
            <option value="this_year" @selected($activePeriod === 'this_year')>Tahun Ini</option>
            <option value="custom" @selected($activePeriod === 'custom')>Rentang Tanggal</option>
        </select>
    </div>

    <div class="dashboard-filter__custom" data-custom-range @if ($activePeriod !== 'custom') hidden @endif>
        <div class="form-group">
            <label class="form-label" for="admin-dashboard-date-from">Tanggal Mulai</label>
            <input
                class="form-control"
                id="admin-dashboard-date-from"
                name="date_from"
                type="date"
                max="{{ now()->toDateString() }}"
                value="{{ $activePeriod === 'custom' ? $dashboard['filters']['date_from'] : '' }}"
            >
        </div>
        <div class="form-group">
            <label class="form-label" for="admin-dashboard-date-to">Tanggal Selesai</label>
            <input
                class="form-control"
                id="admin-dashboard-date-to"
                name="date_to"
                type="date"
                max="{{ now()->toDateString() }}"
                value="{{ $activePeriod === 'custom' ? $dashboard['filters']['date_to'] : '' }}"
            >
        </div>
    </div>

    <div class="dashboard-filter__actions">
        <button class="btn btn-primary" type="submit">Terapkan</button>
        <a class="btn btn-ghost" href="{{ route('dashboard.admin') }}" data-dashboard-reset>Reset</a>
    </div>
</form>
