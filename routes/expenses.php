<?php

use App\Http\Controllers\Expense\ExpenseCategoryController;
use App\Http\Controllers\Expense\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])->group(function (): void {
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])
        ->name('expense-categories.index');
    Route::get('/expense-categories/create', [ExpenseCategoryController::class, 'create'])
        ->name('expense-categories.create');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])
        ->name('expense-categories.store');
    Route::get('/expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])
        ->name('expense-categories.edit');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])
        ->name('expense-categories.update');
    Route::patch('/expense-categories/{expenseCategory}/status', [ExpenseCategoryController::class, 'updateStatus'])
        ->name('expense-categories.status.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])
        ->name('expense-categories.destroy');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::patch('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])
        ->name('expenses.approve');
    Route::patch('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])
        ->name('expenses.reject');
    Route::delete('/expenses/{expense}/proof', [ExpenseController::class, 'destroyProof'])
        ->name('expenses.proof.destroy');
});
