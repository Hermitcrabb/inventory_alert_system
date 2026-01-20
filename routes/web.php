<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProductsController;

Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/debug-route/{path?}', function($path = null) {
    return response()->json([
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'none',
        'path_info' => $_SERVER['PATH_INFO'] ?? 'none',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'none',
        'request_method' => request()->method(),
        'full_url' => request()->fullUrl(),
        'path' => request()->path(),
        'all_routes' => collect(Route::getRoutes()->getRoutes())->map(function($route) {
            return [
                'uri' => $route->uri,
                'methods' => $route->methods,
                'name' => $route->getName()
            ];
        })->toArray()
    ]);
})->where('path', '.*');

// Simple test route
Route::post('/test-post-route', function() {
    return response()->json(['test_post' => 'works']);
});

Route::get('/test-get-route', function() {
    return response()->json(['test_get' => 'works']);
});

// ===== WEBHOOK ROUTES =====
Route::prefix('webhooks')->group(function () {
    Route::post('/inventory-update', [WebhookController::class, 'handleInventoryUpdate'])
        ->name('webhooks.inventory.update');
    
    Route::post('/product-update', [WebhookController::class, 'handleProductUpdate'])
        ->name('webhooks.product.update');
    
    Route::post('/app-uninstalled', [WebhookController::class, 'handleAppUninstalled'])
        ->name('webhooks.app.uninstalled');

    Route::post('/test-simple', function() {
        \Log::info('=== SIMPLE WEBHOOK TEST HIT ===');
        return response()->json(['webhook_test' => 'works'], 200);
    });
});




// // ===== TEMPORARY REDIRECT (Fix path mismatch) =====
// Route::post('/inventory_alert_system/public/webhooks/{endpoint}', function($endpoint) {
//     // Forward to correct endpoint
//     $request = request();
//     $client = new \GuzzleHttp\Client();
    
//     try {
//         $response = $client->post(url("/webhooks/{$endpoint}"), [
//             'headers' => $request->headers->all(),
//             'body' => $request->getContent(),
//             'http_errors' => false
//         ]);
        
//         return response($response->getBody(), $response->getStatusCode());
//     } catch (\Exception $e) {
//         \Log::error('Redirect failed: ' . $e->getMessage());
//         return response()->json(['error' => 'Redirect failed'], 500);
//     }
// })->where('endpoint', '.*');

// // ===== WEBHOOK ROUTES =====
// Route::prefix('webhooks')->group(function () {
//     Route::post('/inventory-update', [WebhookController::class, 'handleInventoryUpdate'])
//         ->name('webhooks.inventory.update');
    
//     Route::post('/product-update', [WebhookController::class, 'handleProductUpdate'])
//         ->name('webhooks.product.update');
    
//     Route::post('/app-uninstalled', [WebhookController::class, 'handleAppUninstalled'])
//         ->name('webhooks.app.uninstalled');

//     // Test endpoint - FIXED: removed duplicate /webhooks
//     Route::post('/test-simple', function() {
//         \Log::info('=== SIMPLE WEBHOOK TEST HIT ===');
//         \Log::info('Headers:', request()->headers->all());
//         \Log::info('Body:', [request()->getContent()]);
    
//         $logEntry = date('Y-m-d H:i:s') . " - Test webhook hit\n";
//         file_put_contents(storage_path('logs/webhook-test.log'), $logEntry, FILE_APPEND);
    
//         return response()->json(['success' => true, 'time' => now()], 200);
//     });
// });

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