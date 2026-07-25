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
require __DIR__.'/management.php';
require __DIR__.'/master-data.php';
require __DIR__.'/products.php';
require __DIR__.'/stocks.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/authorization.php';
}
