<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\CashierReportRequest;
use App\Services\Report\CashierReportService;
use Illuminate\View\View;

class CashierReportController extends AbstractReportController
{
    public function __construct(private readonly CashierReportService $service) {}

    public function index(CashierReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'cashiers');
    }

    public function print(CashierReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'cashiers');
    }
}
