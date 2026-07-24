<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\SystemCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AccountController::class, 'redirect'])
    ->name('home');

Route::get('/system-check', SystemCheckController::class)
    ->name('system-check.index');

Route::get('/design-system', DesignSystemController::class)
    ->name('design-system.index');

require __DIR__.'/auth.php';
