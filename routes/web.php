<?php

use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\SystemCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/system-check', SystemCheckController::class)
    ->name('system-check.index');

Route::get('/design-system', DesignSystemController::class)
    ->name('design-system.index');
