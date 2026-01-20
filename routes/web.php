<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProductsController;

// TEMPORARY DEBUG ROUTE
Route::match(['GET', 'POST'], '/debug-route/{path?}', function($path = null) {
    return response()->json([
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'none',
        'path_info' => $_SERVER['PATH_INFO'] ?? 'none',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'none',
        'request_method' => request()->method(),
        'full_url' => request()->fullUrl(),
        'path' => request()->path(),
        'laravel_working' => true
    ]);
})->where('path', '.*');

// ===== WEBHOOK ROUTES (NO AUTH, NO CSRF) =====
Route::prefix('webhooks')->withoutMiddleware([
    \App\Http\Middleware\VerifyCsrfToken::class
])->group(function () {
    Route::post('/inventory-update', [WebhookController::class, 'handleInventoryUpdate'])
        ->name('webhooks.inventory.update');
    
    Route::post('/product-update', [WebhookController::class, 'handleProductUpdate'])
        ->name('webhooks.product.update');
    
    Route::post('/app-uninstalled', [WebhookController::class, 'handleAppUninstalled'])
        ->name('webhooks.app.uninstalled');

    // Test endpoint - works with GET for browser testing
    Route::match(['GET', 'POST'], '/test-simple', function() {
        \Log::info('=== WEBHOOK TEST HIT ===');
        
        if (request()->method() === 'GET') {
            return "Send POST request to test webhook";
        }
        
        return response()->json([
            'webhook_test' => 'success',
            'time' => now()
        ], 200);
    });
});

// ===== AUTHENTICATED ROUTES =====
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProductsController::class, 'index'])->name('dashboard');
    Route::post('/products/sync', [ProductsController::class, 'manualSync'])->name('products.sync');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::post('/shopify/connect', [ShopifyController::class, 'connect'])->name('shopify.connect');
    Route::get('/shopify/callback', [ShopifyController::class, 'callback'])->name('shopify.callback');
    Route::post('/shopify/disconnect', [ShopifyController::class, 'disconnect'])->name('shopify.disconnect');
});

// ===== PUBLIC ROUTES =====
Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/auth.php';