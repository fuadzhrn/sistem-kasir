<?php

use App\Http\Controllers\StockTransfer\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])->group(function (): void {
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])
        ->name('stock-transfers.index');
    Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])
        ->name('stock-transfers.create');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])
        ->name('stock-transfers.store');
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])
        ->name('stock-transfers.show');
    Route::patch('/stock-transfers/{stockTransfer}/complete', [StockTransferController::class, 'complete'])
        ->name('stock-transfers.complete');
    Route::patch('/stock-transfers/{stockTransfer}/reject', [StockTransferController::class, 'reject'])
        ->name('stock-transfers.reject');
    Route::patch('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])
        ->name('stock-transfers.cancel');
});
