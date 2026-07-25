<?php

use App\Http\Controllers\Stock\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])->group(function (): void {
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/initial', [StockController::class, 'createInitial'])->name('stocks.initial.create');
    Route::post('/stocks/initial', [StockController::class, 'storeInitial'])->name('stocks.initial.store');
    Route::get('/stocks/history', [StockController::class, 'history'])->name('stocks.history.index');
    Route::get('/stocks/{branchStock}', [StockController::class, 'show'])->name('stocks.show');
});
