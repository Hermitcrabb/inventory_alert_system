<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProductsController;

Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/debug-route/{path?}', function ($path = null) {
    return response()->json([
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'none',
        'path_info' => $_SERVER['PATH_INFO'] ?? 'none',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'none',
        'request_method' => request()->method(),
        'full_url' => request()->fullUrl(),
        'path' => request()->path(),
        'all_routes' => collect(Route::getRoutes()->getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri,
                'methods' => $route->methods,
                'name' => $route->getName()
            ];
        })->toArray()
    ]);
})->where('path', '.*');

// Simple test route
Route::post('/test-post-route', function () {
    return response()->json(['test_post' => 'works']);
});

Route::get('/test-get-route', function () {
    return response()->json(['test_get' => 'works']);
});

// ===== WEBHOOK ROUTES =====
Route::prefix('webhooks')->group(function () {
    Route::post('/inventory-update', [WebhookController::class, 'handleInventoryUpdate'])
        ->name('webhooks.inventory.update');

    Route::post('/product-create', [WebhookController::class, 'handleProductCreate'])
        ->name('webhooks.product.create');

    Route::post('/product-update', [WebhookController::class, 'handleProductUpdate'])
        ->name('webhooks.product.update');

    Route::post('/product-delete', [WebhookController::class, 'handleProductDelete'])
        ->name('webhooks.product.delete');

    Route::post('/app-uninstalled', [WebhookController::class, 'handleAppUninstalled'])
        ->name('webhooks.app.uninstalled');

    Route::post('/test-simple', function () {
        \Log::info('=== SIMPLE WEBHOOK TEST HIT ===');
        return response()->json(['webhook_test' => 'works'], 200);
    });
});




// ===== AUTHENTICATED ROUTES =====
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ProductsController::class, 'index'])->name('dashboard');
    Route::post('/products/sync', [ProductsController::class, 'manualSync'])->name('products.sync');
    Route::post('/products/inform-admin', [ProductsController::class, 'informAdmin'])->name('products.inform_admin');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/shopify/connect', [ShopifyController::class, 'connect'])->name('shopify.connect');
    Route::post('/shopify/disconnect', [ShopifyController::class, 'disconnect'])->name('shopify.disconnect');

    // Temporary Email Test Route
    Route::get('/test-email', function () {
        try {
            $user = Auth::user();
            // Create a dummy product for testing
            $product = new \App\Models\Product([
                'title' => 'Test Product',
                'sku' => 'TEST-SKU-123',
                'id' => 1
            ]);

            \Mail::to($user->email)->send(new \App\Mail\LowStockAlert($product, 5, 10));
            return 'Email sent to ' . $user->email . '! Check your inbox.';
        } catch (\Exception $e) {
            return 'Failed to send email: ' . $e->getMessage();
        }
    })->middleware('auth');
});

// ===== PUBLIC ROUTES =====
Route::get('/', function () {
    return view('welcome');
});

require __DIR__ . '/auth.php';