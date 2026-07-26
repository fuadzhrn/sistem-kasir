<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\BranchReportRequest;
use App\Services\Report\BranchReportService;
use Illuminate\View\View;

class BranchReportController extends AbstractReportController
{
    public function __construct(private readonly BranchReportService $service) {}

    public function index(BranchReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'branches');
    }

    public function print(BranchReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'branches');
    }
}
