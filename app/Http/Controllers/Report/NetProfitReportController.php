<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\NetProfitReportRequest;
use App\Services\Report\NetProfitReportService;
use Illuminate\View\View;

class NetProfitReportController extends AbstractReportController
{
    public function __construct(private readonly NetProfitReportService $service) {}

    public function index(NetProfitReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'net-profit');
    }

    public function print(NetProfitReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'net-profit');
    }
}
