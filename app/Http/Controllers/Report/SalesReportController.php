<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\SalesReportRequest;
use App\Services\Report\SalesReportService;
use Illuminate\View\View;

class SalesReportController extends AbstractReportController
{
    public function __construct(private readonly SalesReportService $service) {}

    public function index(SalesReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'sales');
    }

    public function print(SalesReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'sales');
    }
}
