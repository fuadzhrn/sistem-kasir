@if ($report['rows'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="report-pagination">{{ $report['rows']->links() }}</div>
@endif
