<header class="reports-header">
    <div class="reports-header__content">
        <a class="reports-back-link" href="{{ route('reports.index') }}">← Kembali ke Modul Laporan</a>
        <p class="reports-eyebrow">Total berdasarkan seluruh hasil filter</p>
        <h1>{{ $report['title'] }}</h1>
        <p>{{ $report['description'] }}</p>
        <dl class="reports-header__context">
            <div><dt>Periode</dt><dd>{{ $report['period_label'] }}</dd></div>
            <div><dt>Cabang</dt><dd>{{ $report['branch_name'] }}</dd></div>
        </dl>
    </div>
    <div class="reports-header__actions">
        <button
            class="btn btn-secondary mobile-filter-button reports-header__filter-button"
            type="button"
            aria-label="Buka filter {{ $report['title'] }}"
            aria-controls="report-filter-panel"
            aria-expanded="false"
            data-report-filter-toggle
        >
            Filter Laporan
        </button>
        <a class="btn btn-secondary" href="{{ route('reports.'.$report['slug'].'.print', request()->query()) }}" target="_blank" rel="noopener">
            Tampilan Cetak
        </a>
    </div>
</header>
