@php($activePeriod = $dashboard['filters']['period'])

<section class="admin-dashboard__mobile-filter" aria-label="Filter periode dashboard aktif">
    <div class="admin-dashboard__filter-summary">
        <span>Cabang dan periode aktif</span>
        <strong data-active-filter-summary>
            {{ $dashboard['filters']['branch_name'] }} &middot; {{ $dashboard['filters']['period_label'] }}
        </strong>
    </div>

    <button
        class="btn btn-secondary mobile-filter-button admin-dashboard__filter-button"
        type="button"
        data-admin-filter-open
        aria-controls="admin-dashboard-filter"
        aria-expanded="false"
        aria-label="Buka filter periode Dashboard Admin"
    >
        <span aria-hidden="true">☰</span>
        <span>Atur Periode</span>
    </button>
</section>

<div
    class="modal mobile-filter-sheet admin-dashboard__filter-modal"
    id="admin-dashboard-filter"
    data-admin-filter-modal
    aria-labelledby="admin-dashboard-filter-title"
>
    <div class="modal__overlay" data-admin-filter-overlay></div>
    <div class="modal__positioner mobile-filter-sheet__positioner admin-dashboard__filter-positioner">
        <section
            class="modal__dialog mobile-filter-sheet__dialog admin-dashboard__filter-sheet"
            data-admin-filter-dialog
            aria-labelledby="admin-dashboard-filter-title"
            tabindex="-1"
        >
            <header class="modal__header mobile-filter-sheet__header admin-dashboard__filter-sheet-header">
                <div>
                    <p class="dashboard-eyebrow">Cabang {{ $dashboard['filters']['branch_name'] }}</p>
                    <h2 id="admin-dashboard-filter-title">Atur Periode Dashboard</h2>
                    <p>Data tetap dibatasi hanya untuk cabang akun Admin.</p>
                </div>
                <button
                    class="modal__close"
                    type="button"
                    data-admin-filter-close
                    aria-label="Tutup filter periode"
                >
                    &times;
                </button>
            </header>

            <form
                class="dashboard-filter mobile-filter-sheet__body admin-dashboard__filter card"
                method="GET"
                action="{{ route('dashboard.admin') }}"
                data-dashboard-filter
            >
                <div class="admin-dashboard__branch-readonly">
                    <span>Cabang aktif</span>
                    <strong>{{ $dashboard['filters']['branch_name'] }}</strong>
                </div>

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
                    <button class="btn btn-secondary admin-dashboard__filter-close" type="button" data-admin-filter-close>
                        Tutup
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
