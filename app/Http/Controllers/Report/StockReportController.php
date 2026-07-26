<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\StockReportRequest;
use App\Services\Report\StockReportService;
use Illuminate\View\View;

class StockReportController extends AbstractReportController
{
    public function __construct(private readonly StockReportService $service) {}

    public function index(StockReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'stocks');
    }

    public function print(StockReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'stocks');
    }
}
