<?php

namespace App\Http\Controllers\Report;

use App\Http\Requests\Report\ReceiptReportRequest;
use App\Services\Report\ReceiptReportService;
use Illuminate\View\View;

class ReceiptReportController extends AbstractReportController
{
    public function __construct(private readonly ReceiptReportService $service) {}

    public function index(ReceiptReportRequest $request): View
    {
        return $this->indexView($request, $this->service, 'receipts');
    }

    public function print(ReceiptReportRequest $request): View
    {
        return $this->printView($request, $this->service, 'receipts');
    }
}
