<?php

use App\Http\Controllers\Receipt\ReceiptPrintController;
use App\Http\Controllers\Receipt\ReceiptReprintController;
use App\Http\Controllers\Sale\SaleHistoryController;
use App\Http\Controllers\Sale\SaleVoidController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin,cashier'])->group(function (): void {
    Route::get('/sales', [SaleHistoryController::class, 'index'])
        ->name('sales.index');
    Route::patch('/sales/{sale}/void', [SaleVoidController::class, 'store'])
        ->whereNumber('sale')
        ->middleware('throttle:10,1')
        ->name('sales.void');
    Route::get('/sales/{sale}/receipt', [SaleHistoryController::class, 'receipt'])
        ->name('sales.receipt.show');
    Route::get('/sales/{sale}', [SaleHistoryController::class, 'show'])
        ->name('sales.show');
    Route::get('/receipts/{sale}/print', [ReceiptPrintController::class, 'show'])
        ->whereNumber('sale')
        ->name('receipts.print');
    Route::post('/sales/{sale}/receipt/reprint', [ReceiptReprintController::class, 'store'])
        ->whereNumber('sale')
        ->middleware('throttle:10,1')
        ->name('sales.receipt.reprint');
});
