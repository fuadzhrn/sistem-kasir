<?php

use App\Http\Controllers\Setting\StoreLogoController;
use App\Http\Controllers\Setting\StoreSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner'])
    ->prefix('settings/store')
    ->name('settings.store.')
    ->group(function (): void {
        Route::get('/', [StoreSettingController::class, 'index'])->name('index');
        Route::put('/general', [StoreSettingController::class, 'updateGeneral'])->name('general.update');
        Route::put('/receipt', [StoreSettingController::class, 'updateReceipt'])->name('receipt.update');
        Route::put('/business', [StoreSettingController::class, 'updateBusiness'])->name('business.update');
        Route::post('/logo', [StoreLogoController::class, 'update'])->name('logo.update');
        Route::delete('/logo', [StoreLogoController::class, 'destroy'])->name('logo.destroy');
    });
