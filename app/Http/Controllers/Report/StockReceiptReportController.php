<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\StockReceiptReportRequest;
use App\Services\Report\StockReceiptReportService;
use Illuminate\View\View;

class StockReceiptReportController extends AbstractReportController
{
    public function __construct(private readonly StockReceiptReportService $service) {}

    public function index(StockReceiptReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'stock-receipts');
    }

    public function print(StockReceiptReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'stock-receipts');
    }
}
