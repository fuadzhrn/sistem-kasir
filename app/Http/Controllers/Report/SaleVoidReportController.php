<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\SaleVoidReportRequest;
use App\Services\Report\SaleVoidReportService;
use Illuminate\View\View;

class SaleVoidReportController extends AbstractReportController
{
    public function __construct(private readonly SaleVoidReportService $service) {}

    public function index(SaleVoidReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'sale-voids');
    }

    public function print(SaleVoidReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'sale-voids');
    }
}
