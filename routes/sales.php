<?php

use App\Http\Controllers\Sale\SaleHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin,cashier'])->group(function (): void {
    Route::get('/sales', [SaleHistoryController::class, 'index'])
        ->name('sales.index');
    Route::get('/sales/{sale}/receipt', [SaleHistoryController::class, 'receipt'])
        ->name('sales.receipt.show');
    Route::get('/sales/{sale}', [SaleHistoryController::class, 'show'])
        ->name('sales.show');
});
