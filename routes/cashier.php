<?php

use App\Http\Controllers\Cashier\CashierCheckoutController;
use App\Http\Controllers\Cashier\CashierController;
use App\Http\Controllers\Cashier\CashierProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin,cashier'])->group(function (): void {
    Route::get('/cashier', [CashierController::class, 'index'])
        ->name('cashier.index');
    Route::get('/cashier/products', [CashierProductController::class, 'index'])
        ->middleware('throttle:90,1')
        ->name('cashier.products.index');
    Route::post('/cashier/checkout', [CashierCheckoutController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('cashier.checkout.store');
});
