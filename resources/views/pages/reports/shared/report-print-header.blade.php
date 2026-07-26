<header class="report-print-header">
    <p>{{ config('app.name') }}</p><h1>{{ $report['title'] }}</h1>
    <dl><div><dt>Cabang</dt><dd>{{ $report['branch_name'] }}</dd></div><div><dt>Periode</dt><dd>{{ $report['period_label'] }}</dd></div><div><dt>Dicetak</dt><dd>{{ $report['printed_at'] }}</dd></div><div><dt>Pengguna</dt><dd>{{ $report['printed_by'] }}</dd></div></dl>
    <p class="report-print-header__filters">
        <strong>Filter aktif:</strong>
        @forelse ($report['active_filters'] as $filter)
            {{ $filter['label'] }}: {{ $filter['value'] }}@unless($loop->last) · @endunless
        @empty
            Tidak ada filter tambahan
        @endforelse
    </p>
</header>
