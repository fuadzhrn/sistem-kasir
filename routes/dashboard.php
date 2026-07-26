<?php

use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CashierDashboardController;
use App\Http\Controllers\Dashboard\DashboardRedirectController;
use App\Http\Controllers\Dashboard\OwnerDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])
    ->get('/dashboard', DashboardRedirectController::class)
    ->name('dashboard');

Route::middleware(['auth', 'active.user', 'role:owner'])
    ->prefix('dashboard/owner')
    ->group(function (): void {
        Route::get('/', [OwnerDashboardController::class, 'index'])
            ->name('dashboard.owner');
        Route::get('/data', [OwnerDashboardController::class, 'data'])
            ->middleware('throttle:60,1')
            ->name('dashboard.owner.data');
    });

Route::middleware(['auth', 'active.user', 'role:admin'])
    ->prefix('dashboard/admin')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])
            ->name('dashboard.admin');
        Route::get('/data', [AdminDashboardController::class, 'data'])
            ->middleware('throttle:60,1')
            ->name('dashboard.admin.data');
    });

Route::middleware(['auth', 'active.user', 'role:cashier'])
    ->get('/dashboard/cashier', [CashierDashboardController::class, 'index'])
    ->name('dashboard.cashier');
