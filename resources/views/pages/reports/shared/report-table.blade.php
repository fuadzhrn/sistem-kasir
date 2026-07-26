<div class="report-table-wrap">
<table class="report-table"><thead><tr>@foreach($report['columns'] as $column)<th scope="col">{{ $column['label'] }}</th>@endforeach</tr></thead>
<tbody>
@forelse($report['rows'] as $row)
<tr>@foreach($report['columns'] as $column)<td>@if(!$report['for_print'] && isset($column['link']) && filled($row[$column['link']] ?? null))<a href="{{ $row[$column['link']] }}">{{ $row[$column['key']] ?? '—' }}</a>@else{{ $row[$column['key']] ?? '—' }}@endif</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($report['columns']) }}">Tidak ada data yang sesuai dengan filter.</td></tr>@endforelse
</tbody><tfoot><tr><td colspan="{{ count($report['columns']) }}">Total berdasarkan seluruh hasil filter tersedia pada ringkasan.</td></tr></tfoot></table>
</div>
