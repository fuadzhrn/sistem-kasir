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
require __DIR__.'/stock-receipts.php';
require __DIR__.'/stock-adjustments.php';
require __DIR__.'/stock-transfers.php';
require __DIR__.'/cashier.php';
require __DIR__.'/sales.php';
require __DIR__.'/expenses.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/reports.php';
require __DIR__.'/activities.php';
require __DIR__.'/settings.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/authorization.php';
}
