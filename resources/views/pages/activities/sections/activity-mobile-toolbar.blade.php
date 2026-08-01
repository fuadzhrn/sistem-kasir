<section class="activities-mobile-toolbar" aria-label="Periode, cabang, dan filter aktivitas">
    <dl>
        <div><dt>Periode</dt><dd>{{ $periodLabel }}</dd></div>
        <div><dt>Cabang</dt><dd>{{ $branchLabel }}</dd></div>
    </dl>
    <button
        class="btn btn-secondary mobile-filter-button"
        type="button"
        aria-label="Buka filter Audit Aktivitas"
        aria-controls="activity-filter-panel"
        aria-expanded="false"
        data-activity-filter-toggle
    >
        Filter
    </button>
</section>
