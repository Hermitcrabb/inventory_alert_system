<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\AlertHistoryController;

// ===== WEBHOOK ROUTES =====
Route::prefix('webhooks')->group(function () {
    // Generic handler for all Shopify webhooks
    Route::post('/{type}', [WebhookController::class, 'handle'])->name('webhooks.shopify');
});

// ===== AUTHENTICATED ROUTES =====
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProductsController::class, 'index'])->name('dashboard');
    Route::get('/alerts/history', [AlertHistoryController::class, 'index'])->name('alerts.history');

    Route::post('/products/sync', [ProductsController::class, 'manualSync'])->name('products.sync');
    Route::post('/products/update-quantity', [ProductsController::class, 'updateQuantity'])->name('products.update_quantity');
    Route::delete('/products/delete', [ProductsController::class, 'deleteProduct'])->name('products.delete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== PUBLIC ROUTES =====
Route::get('/', function () {
    return view('welcome');
});

require __DIR__ . '/auth.php';