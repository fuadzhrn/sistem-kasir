@if ($report['rows'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    {{ $report['rows']->onEachSide(1)->links('components.pagination', ['itemLabel' => 'data laporan']) }}
@endif
