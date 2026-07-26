<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\View\View;

abstract class AbstractReportController extends Controller
{
    protected function indexView(FormRequest $request, ReportService $service, string $folder): View
    {
        return view("pages.reports.$folder.index", [
            'report' => $service->build($request->user(), $request->validated()),
        ]);
    }

    protected function printView(FormRequest $request, ReportService $service, string $folder): View
    {
        return view("pages.reports.$folder.print", [
            'report' => $service->build($request->user(), $request->validated(), true),
        ]);
    }
}
