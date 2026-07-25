<?php

use App\Http\Controllers\AuthorizationCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])
    ->prefix('authorization-check')
    ->name('authorization-check.')
    ->group(function (): void {
        Route::get('/', [AuthorizationCheckController::class, 'index'])
            ->name('index');

        Route::get('/owner', [AuthorizationCheckController::class, 'owner'])
            ->middleware('role:owner')
            ->name('owner');
        Route::get('/management', [AuthorizationCheckController::class, 'management'])
            ->middleware('role:owner,admin')
            ->name('management');
        Route::get('/cashier', [AuthorizationCheckController::class, 'cashier'])
            ->middleware('role:owner,admin,cashier')
            ->name('cashier');

        Route::get('/branches/{branch}', [AuthorizationCheckController::class, 'branch'])
            ->middleware('branch.access')
            ->name('branch');
        Route::get('/profit/{branch}', [AuthorizationCheckController::class, 'profit'])
            ->name('profit');
    });
