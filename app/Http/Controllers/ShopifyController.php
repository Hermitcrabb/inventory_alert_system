<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;
use App\Services\Shopify\WebhookService;

class ShopifyController extends Controller
{
    public function connect(Request $request)
    {
        $request->validate([
            'shop' => 'required|string',
            'access_token' => 'required|string'
        ]);
        
        $shopDomain = $request->input('shop');
        $accessToken = $request->input('access_token');
        
        // Clean up shop domain
        $shopDomain = str_replace(['https://', 'http://'], '', $shopDomain);
        $shopDomain = rtrim($shopDomain, '/');
        
        // Validate shop domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shopDomain)) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid Shopify domain format. Use: your-store.myshopify.com');
        }
        
        try {
            // Test the connection with a simple API call
            $restService = new RestService($shopDomain, $accessToken);
            $shopInfo = $restService->getShopInfo();
            
            // Save shop to database
            $shop = Shop::updateOrCreate(
                ['shopify_domain' => $shopDomain],
                [
                    'name' => $shopInfo['name'] ?? $shopDomain,
                    'access_token' => $accessToken,
                    'email' => $shopInfo['email'] ?? 'unknown@example.com',
                    'shop_owner' => $shopInfo['shop_owner'] ?? 'Unknown',
                    'status' => 'active',
                ]
            );
            
            // Attach user to shop
            $user = Auth::user();
            $shop->users()->syncWithoutDetaching([
                $user->id => ['role' => 'owner']
            ]);
            
            // Register webhooks using WebhookService
            $this->registerWebhooks($shop);
            
            // Initial product sync
            $this->syncProducts($shop);
            
            Log::info('Shopify store connected via API token', [
                'shop_id' => $shop->id,
                'shop_domain' => $shopDomain,
                'user_id' => $user->id
            ]);
            
            return redirect()->route('dashboard')
                ->with('success', 'Shopify store connected successfully! Products are syncing in background.');
                
        } catch (\Exception $e) {
            Log::error('Shopify API connection failed', [
                'error' => $e->getMessage(),
                'shop' => $shopDomain,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }
    
    /**
     * Register webhooks for the shop
     */
    private function registerWebhooks(Shop $shop): void
    {
        try {
            $webhookService = new \App\Services\Shopify\WebhookService(
                $shop->shopify_domain,
                $shop->access_token
            );
            
            // Use the new method name
            $results = $webhookService->registerWebhooksForShop($shop);
            
            Log::info('Webhooks registration results', [
                'shop' => $shop->shopify_domain,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to register webhooks', [
                'shop' => $shop->shopify_domain,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sync products from Shopify
     */
    private function syncProducts(Shop $shop): void
    {
        // Dispatch sync job to queue
        \App\Jobs\SyncShopifyProducts::dispatch($shop);
        
        Log::info('Product sync job dispatched', ['shop' => $shop->shopify_domain]);
    }
    
    /**
     * Disconnect a shop
     */
    public function disconnect(Request $request)
    {
        try {
            $request->validate([
                'shop_id' => 'required|exists:shops,id'
            ]);
            
            $user = Auth::user();
            $shop = Shop::findOrFail($request->input('shop_id'));
            
            // Check if user is associated with this shop
            if (!$shop->users()->where('user_id', $user->id)->exists()) {
                return redirect()->route('dashboard')
                    ->with('error', 'You are not authorized to disconnect this shop.');
            }
            
            // Remove user from shop
            $shop->users()->detach($user->id);
            
            if ($shop->users()->count() === 0) {
                $shop->delete();
                $message = 'Shop disconnected and removed.';
            } else {
                $message = 'You have been disconnected from the shop.';
            }
            
            Log::info('Shop disconnected', [
                'shop_id' => $shop->id,
                'user_id' => $user->id
            ]);
            
            return redirect()->route('dashboard')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Shop disconnect failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Failed to disconnect from shop.');
        }
    }
}