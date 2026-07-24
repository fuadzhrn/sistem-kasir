<?php

use App\Http\Controllers\SystemCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/system-check', SystemCheckController::class)
    ->name('system-check.index');
