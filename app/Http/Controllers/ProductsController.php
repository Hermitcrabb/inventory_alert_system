<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Models\Product;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get store domain and token from .env
        $storeDomain = config('services.shopify.store_domain');
        $adminToken = config('services.shopify.admin_token');

        if (!$storeDomain || !$adminToken) {
            return view('dashboard', [
                'products' => collect(),
                'shops' => collect(),
                'lowStockCount' => 0,
                'outOfStockCount' => 0,
                'error' => 'Shopify credentials not configured in .env file'
            ]);
        }

        // Check if store is already connected
        $shop = Shop::where('shopify_domain', $storeDomain)->first();

        // If not connected, auto-connect it
        if (!$shop) {
            try {
                $shop = $this->autoConnectStore($storeDomain, $adminToken, $user);
            } catch (\Exception $e) {
                Log::error('Auto-connect failed', ['error' => $e->getMessage()]);
                return view('dashboard', [
                    'products' => collect(),
                    'shops' => collect(),
                    'lowStockCount' => 0,
                    'outOfStockCount' => 0,
                    'error' => 'Failed to connect to Shopify: ' . $e->getMessage()
                ]);
            }
        }

        // Get all products
        $products = Product::where('shop_id', $shop->id)
            ->where('sku', '!=', 'N/A') // Skip placeholder SKUs
            ->whereNotNull('sku') // Skip null SKUs
            ->where('current_inventory', '>=', 0) // Only products with inventory tracking
            ->orderBy('current_inventory', 'asc')
            ->orderBy('title', 'asc')
            ->paginate(20);

        // Get low stock products
        $lowStockCount = Product::where('shop_id', $shop->id)
            ->where('sku', '!=', 'N/A')
            ->whereNotNull('sku')
            ->where('current_inventory', '>', 0)
            ->where('current_inventory', '<=', 20)
            ->count();

        // Get out of stock products
        $outOfStockCount = Product::where('shop_id', $shop->id)
            ->where('sku', '!=', 'N/A')
            ->whereNotNull('sku')
            ->where('current_inventory', '<=', 0)
            ->count();

        return view('dashboard', [
            'products' => $products,
            'shops' => collect([$shop]),
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lastSynced' => $shop->last_synced_at
        ]);
    }

    public function informAdmin(Request $request)
    {
        Log::info('informAdmin method triggered');
        $user = Auth::user();
        Log::info('User identified', ['email' => $user->email]);

        $storeDomain = config('services.shopify.store_domain');
        $shop = Shop::where('shopify_domain', $storeDomain)->first();

        if (!$shop) {
            Log::warning('Shop not found for domain', ['domain' => $storeDomain]);
            return redirect()->back()->with('error', 'Store not connected.');
        }

        // Get products with quantity <= 10
        $lowStockProducts = Product::where('shop_id', $shop->id)
            ->where('current_inventory', '<=', 10)
            ->whereNotNull('sku')
            ->where('sku', '!=', 'N/A')
            ->get();

        Log::info('Low stock products search', ['count' => $lowStockProducts->count()]);

        if ($lowStockProducts->isEmpty()) {
            return redirect()->back()->with('success', 'No low stock items found to report.');
        }

        try {
            Log::info('Attempting to send mail to ' . $user->email);
            // Send email to the CURRENTLY LOGGED IN user
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\LowStockReport($lowStockProducts));

            Log::info('Mail send command executed successfully');
            return redirect()->back()->with('success', 'Low stock report sent successfully to ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to send low stock report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to send report: ' . $e->getMessage());
        }
    }

    private function autoConnectStore(string $storeDomain, string $adminToken, $user)
    {
        // Test the connection
        $restService = new RestService($storeDomain, $adminToken);
        $shopInfo = $restService->getShopInfo();

        // Create or update shop
        $shop = Shop::updateOrCreate(
            ['shopify_domain' => $storeDomain],
            [
                'name' => $shopInfo['name'] ?? $storeDomain,
                'access_token' => $adminToken,
                'email' => $shopInfo['email'] ?? 'unknown@example.com',
                'shop_owner' => $shopInfo['shop_owner'] ?? 'Unknown',
                'status' => 'active',
            ]
        );

        // Attach to current user
        $shop->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner']
        ]);

        Log::info('Shop auto-connected', [
            'shop_id' => $shop->id,
            'shop_domain' => $storeDomain,
            'user_id' => $user->id
        ]);

        return $shop;
    }

    private function syncProducts(Shop $shop): void
    {
        // Dispatch sync job to queue
        \App\Jobs\SyncShopifyProducts::dispatch($shop);

        // Update last sync time
        $shop->update(['last_synced_at' => now()]);

        Log::info('Product sync initiated', ['shop' => $shop->shopify_domain]);
    }

    public function manualSync(Request $request)
    {
        $storeDomain = config('services.shopify.store_domain');
        $shop = Shop::where('shopify_domain', $storeDomain)->first();

        if (!$shop) {
            Log::error('Manual Sync: Shop not found', ['domain' => $storeDomain]);
            return redirect()->route('dashboard')
                ->with('error', 'Store not found. Please check your .env configuration.');
        }

        $this->syncProducts($shop);

        return redirect()->route('dashboard')
            ->with('success', 'Product sync initiated. Please refresh in a few moments.');
    }
}
