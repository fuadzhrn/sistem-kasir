<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\PriceHistoryReportRequest;
use App\Services\Report\PriceHistoryReportService;
use Illuminate\View\View;

class PriceHistoryReportController extends AbstractReportController
{
    public function __construct(private readonly PriceHistoryReportService $service) {}

    public function index(PriceHistoryReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'price-histories');
    }

    public function print(PriceHistoryReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'price-histories');
    }
}
