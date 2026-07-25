<?php

use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Unit\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])->group(function (): void {
    Route::middleware('role:owner,admin')->group(function (): void {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/status', [CategoryController::class, 'updateStatus'])
            ->name('categories.status.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/units/create', [UnitController::class, 'create'])->name('units.create');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/units/{unit}', [UnitController::class, 'show'])->name('units.show');
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::patch('/units/{unit}/status', [UnitController::class, 'updateStatus'])->name('units.status.update');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    });

    Route::middleware('role:owner')->group(function (): void {
        Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])
            ->name('payment-methods.create');
        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::get('/payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])
            ->name('payment-methods.edit');
        Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])
            ->name('payment-methods.update');
        Route::patch('/payment-methods/{paymentMethod}/status', [PaymentMethodController::class, 'updateStatus'])
            ->name('payment-methods.status.update');
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])
            ->name('payment-methods.destroy');
    });

    Route::get('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show'])
        ->middleware('role:owner,admin')
        ->name('payment-methods.show');
});
