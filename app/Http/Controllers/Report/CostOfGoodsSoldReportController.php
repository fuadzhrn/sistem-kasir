<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\CostOfGoodsSoldReportRequest;
use App\Services\Report\CostOfGoodsSoldReportService;
use Illuminate\View\View;

class CostOfGoodsSoldReportController extends AbstractReportController
{
    public function __construct(private readonly CostOfGoodsSoldReportService $service) {}

    public function index(CostOfGoodsSoldReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'cost-of-goods-sold');
    }

    public function print(CostOfGoodsSoldReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'cost-of-goods-sold');
    }
}
