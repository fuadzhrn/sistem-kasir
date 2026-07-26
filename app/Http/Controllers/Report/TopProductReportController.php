<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\TopProductReportRequest;
use App\Services\Report\TopProductReportService;
use Illuminate\View\View;

class TopProductReportController extends AbstractReportController
{
    public function __construct(private readonly TopProductReportService $service) {}

    public function index(TopProductReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'top-products');
    }

    public function print(TopProductReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'top-products');
    }
}
