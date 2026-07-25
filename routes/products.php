<?php

use App\Http\Controllers\Product\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user', 'role:owner,admin'])->group(function (): void {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/status', [ProductController::class, 'updateStatus'])
        ->name('products.status.update');
    Route::delete('/products/{product}/image', [ProductController::class, 'destroyImage'])
        ->name('products.image.destroy');
    Route::get('/products/{product}/price-history', [ProductController::class, 'priceHistory'])
        ->name('products.price-history.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
});
