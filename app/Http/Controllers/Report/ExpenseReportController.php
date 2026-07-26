<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\ExpenseReportRequest;
use App\Services\Report\ExpenseReportService;
use Illuminate\View\View;

class ExpenseReportController extends AbstractReportController
{
    public function __construct(private readonly ExpenseReportService $service) {}

    public function index(ExpenseReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'expenses');
    }

    public function print(ExpenseReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'expenses');
    }
}
