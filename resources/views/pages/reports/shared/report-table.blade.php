@php
    $complexReportSlugs = [
        'cost-of-goods-sold',
        'gross-profit',
        'net-profit',
        'stock-movements',
        'branches',
    ];
    $usesComplexTable = in_array($report['slug'], $complexReportSlugs, true);
@endphp

@if (! $report['for_print'] && $usesComplexTable)
    <p class="report-table-scroll__hint" id="report-table-scroll-hint-{{ $report['slug'] }}">
        Geser tabel ke samping untuk melihat kolom lainnya.
    </p>
@endif

<div
    class="report-table-wrap ui-table-scroll {{ ! $report['for_print'] && ! $usesComplexTable ? 'report-table-wrap--desktop-only' : '' }} {{ ! $report['for_print'] && $usesComplexTable ? 'report-table-wrap--complex' : '' }}"
    @if (! $report['for_print'] && $usesComplexTable)
        role="region"
        aria-labelledby="report-table-scroll-hint-{{ $report['slug'] }}"
        tabindex="0"
        data-report-table-scroll
    @endif
>
    <table class="report-table">
        <thead>
            <tr>
                @foreach ($report['columns'] as $column)
                    <th scope="col">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($report['columns'] as $column)
                        <td>
                            @if (! $report['for_print'] && isset($column['link']) && filled($row[$column['link']] ?? null))
                                @if (($column['method'] ?? 'get') === 'post')
                                    <form method="POST" action="{{ $row[$column['link']] }}" target="_blank" rel="noopener">
                                        @csrf
                                        <button class="report-table__link-button" type="submit">{{ $row[$column['key']] ?? '—' }}</button>
                                    </form>
                                @else
                                    <a href="{{ $row[$column['link']] }}">{{ $row[$column['key']] ?? '—' }}</a>
                                @endif
                            @else
                                {{ $row[$column['key']] ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['columns']) }}">Tidak ada data yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ count($report['columns']) }}">Total berdasarkan seluruh hasil filter tersedia pada ringkasan.</td>
            </tr>
        </tfoot>
    </table>
</div>

@if (! $report['for_print'] && ! $usesComplexTable)
    @include('pages.reports.shared.report-card-list')
@endif
