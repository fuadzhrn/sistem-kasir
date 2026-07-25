<?php

use App\Http\Controllers\Dashboard\OwnerDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner'])
    ->prefix('dashboard/owner')
    ->group(function (): void {
        Route::get('/', [OwnerDashboardController::class, 'index'])
            ->name('dashboard.owner');
        Route::get('/data', [OwnerDashboardController::class, 'data'])
            ->middleware('throttle:60,1')
            ->name('dashboard.owner.data');
    });
