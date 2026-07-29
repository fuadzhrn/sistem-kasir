@php
    $activePeriod = $dashboard['filters']['period'];
@endphp

<section class="owner-dashboard__mobile-filter" aria-label="Filter dashboard aktif">
    <div class="owner-dashboard__filter-summary">
        <span>Data yang ditampilkan</span>
        <strong data-active-filter-summary>
            {{ $dashboard['filters']['period_label'] }} &middot; {{ $dashboard['filters']['branch_name'] }}
        </strong>
    </div>

    <button
        class="btn btn-secondary owner-dashboard__filter-button"
        type="button"
        data-dashboard-filter-open
        aria-controls="owner-dashboard-filter"
        aria-expanded="false"
        aria-label="Buka filter Dashboard Owner"
    >
        <span aria-hidden="true">☰</span>
        <span>Atur Filter</span>
    </button>
</section>

<div
    class="modal dashboard-filter-modal"
    id="owner-dashboard-filter"
    data-dashboard-filter-modal
    aria-labelledby="owner-dashboard-filter-title"
>
    <div class="modal__overlay" data-dashboard-filter-overlay></div>
    <div class="modal__positioner dashboard-filter-modal__positioner">
        <section
            class="modal__dialog dashboard-filter-sheet"
            data-dashboard-filter-dialog
            aria-labelledby="owner-dashboard-filter-title"
            tabindex="-1"
        >
            <header class="modal__header dashboard-filter-sheet__header">
                <div>
                    <p class="dashboard-eyebrow">Dashboard Owner</p>
                    <h2 id="owner-dashboard-filter-title">Atur Filter Dashboard</h2>
                    <p>Pilih periode dan cabang, kemudian tekan Terapkan.</p>
                </div>
                <button
                    class="modal__close"
                    type="button"
                    data-dashboard-filter-close
                    aria-label="Tutup filter dashboard"
                >
                    &times;
                </button>
            </header>

            <form
                class="dashboard-filter card"
                method="GET"
                action="{{ route('dashboard.owner') }}"
                data-dashboard-filter
            >
                <div class="form-group">
                    <label class="form-label" for="dashboard-branch">Cabang</label>
                    <select class="form-control" id="dashboard-branch" name="branch_id">
                        <option value="">Semua Cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $dashboard['filters']['branch_id'] === (string) $branch->id)>
                                {{ $branch->name }}{{ $branch->is_active ? '' : ' (Nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dashboard-period">Periode</label>
                    <select class="form-control" id="dashboard-period" name="period" data-period-select>
                        <option value="today" @selected($activePeriod === 'today')>Hari Ini</option>
                        <option value="this_week" @selected($activePeriod === 'this_week')>Minggu Ini</option>
                        <option value="this_month" @selected($activePeriod === 'this_month')>Bulan Ini</option>
                        <option value="this_year" @selected($activePeriod === 'this_year')>Tahun Ini</option>
                        <option value="custom" @selected($activePeriod === 'custom')>Rentang Tanggal</option>
                    </select>
                </div>

                <div class="dashboard-filter__custom" data-custom-range @if ($activePeriod !== 'custom') hidden @endif>
                    <div class="form-group">
                        <label class="form-label" for="dashboard-date-from">Tanggal Mulai</label>
                        <input
                            class="form-control"
                            id="dashboard-date-from"
                            name="date_from"
                            type="date"
                            max="{{ now()->toDateString() }}"
                            value="{{ $activePeriod === 'custom' ? $dashboard['filters']['date_from'] : '' }}"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dashboard-date-to">Tanggal Selesai</label>
                        <input
                            class="form-control"
                            id="dashboard-date-to"
                            name="date_to"
                            type="date"
                            max="{{ now()->toDateString() }}"
                            value="{{ $activePeriod === 'custom' ? $dashboard['filters']['date_to'] : '' }}"
                        >
                    </div>
                </div>

                <div class="dashboard-filter__actions">
                    <button class="btn btn-primary" type="submit">Terapkan</button>
                    <a class="btn btn-ghost" href="{{ route('dashboard.owner') }}" data-dashboard-reset>Reset</a>
                    <button class="btn btn-secondary dashboard-filter__close" type="button" data-dashboard-filter-close>
                        Tutup
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
