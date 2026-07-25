<?php

use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])->group(function (): void {
    Route::get('/my-branch', [BranchController::class, 'showMyBranch'])
        ->middleware('role:owner,admin')
        ->name('my-branch.show');

    Route::middleware('role:owner')->group(function (): void {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::patch('/branches/{branch}/status', [BranchController::class, 'updateStatus'])->name('branches.status.update');
    });

    Route::middleware('role:owner,admin')->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });

    Route::middleware('role:owner')->group(function (): void {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
        Route::get('/users/{user}/reset-password', [UserController::class, 'editPassword'])->name('users.password.edit');
        Route::put('/users/{user}/reset-password', [UserController::class, 'updatePassword'])->name('users.password.update');
    });

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('role:owner,admin')
        ->name('users.show');
});
