<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\StockMovementReportRequest;
use App\Services\Report\StockMovementReportService;
use Illuminate\View\View;

class StockMovementReportController extends AbstractReportController
{
    public function __construct(private readonly StockMovementReportService $service) {}

    public function index(StockMovementReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'stock-movements');
    }

    public function print(StockMovementReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'stock-movements');
    }
}
