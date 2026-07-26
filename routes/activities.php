<?php

use App\Http\Controllers\Activity\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])
    ->prefix('activities')
    ->name('activities.')
    ->group(function (): void {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])
            ->whereNumber('activityLog')
            ->name('show');
    });
