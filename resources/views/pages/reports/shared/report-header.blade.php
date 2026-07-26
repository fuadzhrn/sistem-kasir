<header class="reports-header">
    <div>
        <a class="reports-back-link" href="{{ route('reports.index') }}">← Kembali ke Modul Laporan</a>
        <p class="reports-eyebrow">Total berdasarkan seluruh hasil filter</p>
        <h1>{{ $report['title'] }}</h1>
        <p>{{ $report['description'] }}</p>
        <p><strong>{{ $report['branch_name'] }}</strong> · {{ $report['period_label'] }}</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('reports.'.$report['slug'].'.print', request()->query()) }}" target="_blank" rel="noopener">
        Tampilan Cetak
    </a>
</header>
