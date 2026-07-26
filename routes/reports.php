<?php

use App\Http\Controllers\Report\BranchReportController;
use App\Http\Controllers\Report\CashierReportController;
use App\Http\Controllers\Report\CostOfGoodsSoldReportController;
use App\Http\Controllers\Report\ExpenseReportController;
use App\Http\Controllers\Report\GrossProfitReportController;
use App\Http\Controllers\Report\NetProfitReportController;
use App\Http\Controllers\Report\PriceHistoryReportController;
use App\Http\Controllers\Report\ReceiptReportController;
use App\Http\Controllers\Report\ReportIndexController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Report\SaleVoidReportController;
use App\Http\Controllers\Report\StockMovementReportController;
use App\Http\Controllers\Report\StockReceiptReportController;
use App\Http\Controllers\Report\StockReportController;
use App\Http\Controllers\Report\TopProductReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        Route::get('/', ReportIndexController::class)->name('index');

        $reports = [
            'sales' => SalesReportController::class,
            'receipts' => ReceiptReportController::class,
            'cost-of-goods-sold' => CostOfGoodsSoldReportController::class,
            'gross-profit' => GrossProfitReportController::class,
            'net-profit' => NetProfitReportController::class,
            'expenses' => ExpenseReportController::class,
            'stocks' => StockReportController::class,
            'stock-receipts' => StockReceiptReportController::class,
            'stock-movements' => StockMovementReportController::class,
            'top-products' => TopProductReportController::class,
            'branches' => BranchReportController::class,
            'cashiers' => CashierReportController::class,
            'price-histories' => PriceHistoryReportController::class,
            'sale-voids' => SaleVoidReportController::class,
        ];

        foreach ($reports as $slug => $controller) {
            Route::get("/$slug", [$controller, 'index'])->name("$slug.index");
            Route::get("/$slug/print", [$controller, 'print'])->name("$slug.print");
        }
    });
