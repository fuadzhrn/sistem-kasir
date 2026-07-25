<?php

use App\Http\Controllers\StockReceipt\StockReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])->group(function (): void {
    Route::get('/stock-receipts', [StockReceiptController::class, 'index'])
        ->name('stock-receipts.index');
    Route::get('/stock-receipts/create', [StockReceiptController::class, 'create'])
        ->name('stock-receipts.create');
    Route::post('/stock-receipts', [StockReceiptController::class, 'store'])
        ->name('stock-receipts.store');
    Route::get('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'show'])
        ->name('stock-receipts.show');
});
