@if ($report['rows'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="reports-pagination">
        {{ $report['rows']->onEachSide(1)->links('components.pagination', ['itemLabel' => 'data laporan']) }}
    </div>
@endif
