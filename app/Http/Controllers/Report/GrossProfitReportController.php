<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\GrossProfitReportRequest;
use App\Services\Report\GrossProfitReportService;
use Illuminate\View\View;

class GrossProfitReportController extends AbstractReportController
{
    public function __construct(private readonly GrossProfitReportService $service) {}

    public function index(GrossProfitReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'gross-profit');
    }

    public function print(GrossProfitReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'gross-profit');
    }
}
