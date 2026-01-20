<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Shop;
use App\Models\Product;

class WebhookController extends Controller
{
    /**
     * Handle inventory update webhook from Shopify
     */
    public function handleInventoryUpdate(Request $request)
    {
        Log::info('=== WEBHOOK RECEIVED: Inventory Update ===');
        
        // Verify webhook signature
        if (!$this->verifyWebhook($request)) {
            Log::warning('Invalid webhook signature', [
                'hmac_received' => $request->header('X-Shopify-Hmac-SHA256'),
                'shop_domain' => $request->header('X-Shopify-Shop-Domain')
            ]);
            abort(401, 'Invalid webhook signature');
        }
        
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $topic = $request->header('X-Shopify-Topic');
        $webhookId = $request->header('X-Shopify-Webhook-Id');
        
        Log::info('Webhook validated', [
            'shop' => $shopDomain,
            'topic' => $topic,
            'webhook_id' => $webhookId
        ]);
        
        try {
            // Find shop
            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if (!$shop) {
                Log::error('Shop not found for webhook', ['domain' => $shopDomain]);
                return response()->json(['error' => 'Shop not found'], 404);
            }
            
            // Process inventory update
            $result = $this->processInventoryUpdate($shop, $request->all());
            
            Log::info('Webhook processed successfully', [
                'shop' => $shopDomain,
                'product_updated' => $result['product_updated'] ?? false,
                'inventory_changed' => $result['inventory_changed'] ?? false,
                'threshold_check' => $result['threshold_check'] ?? false
            ]);
            
            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'shop' => $shopDomain,
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    /**
     * Handle product update webhook from Shopify
     */
    public function handleProductUpdate(Request $request)
    {
        Log::info('=== WEBHOOK RECEIVED: Product Update ===');
        
        if (!$this->verifyWebhook($request)) {
            abort(401, 'Invalid webhook signature');
        }
        
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        
        try {
            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if (!$shop) {
                return response()->json(['error' => 'Shop not found'], 404);
            }
            
            // Update product information
            $this->processProductUpdate($shop, $request->all());
            
            Log::info('Product update processed', ['shop' => $shopDomain]);
            
            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            Log::error('Product update webhook failed', [
                'shop' => $shopDomain,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    /**
     * Verify Shopify webhook signature
     */
    private function verifyWebhook(Request $request): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        
        if (!$hmacHeader) {
            return false;
        }
        
        $data = $request->getContent();
        $secret = config('services.shopify.webhook_secret');
        
        if (!$secret) {
            Log::warning('Webhook secret not configured');
            return false;
        }
        
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $data, $secret, true)
        );
        
        return hash_equals($hmacHeader, $calculatedHmac);
    }
    
    /**
     * Process inventory update payload
     */
    private function processInventoryUpdate(Shop $shop, array $data): array
    {
        Log::debug('Processing inventory update', $data);
        
        $inventoryItemId = $data['inventory_item_id'] ?? null;
        $locationId = $data['location_id'] ?? null;
        $available = (int) ($data['available'] ?? 0);
        $updatedAt = $data['updated_at'] ?? now()->toISOString();
        
        if (!$inventoryItemId) {
            throw new \Exception('Missing inventory_item_id in webhook');
        }
        
        // Find product by inventory item ID
        $product = Product::where('shop_id', $shop->id)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();
        
        if (!$product) {
            // Try to find by extracting ID from inventory_item_id
            $extractedId = $this->extractIdFromGid($inventoryItemId);
            $product = Product::where('shop_id', $shop->id)
                ->where('inventory_item_id', 'like', "%{$extractedId}%")
                ->first();
        }
        
        if (!$product) {
            Log::warning('Product not found for inventory update', [
                'inventory_item_id' => $inventoryItemId,
                'shop_id' => $shop->id
            ]);
            return ['product_updated' => false];
        }
        
        $oldInventory = $product->current_inventory;
        
        // Update product inventory
        $product->update([
            'current_inventory' => $available,
            'last_synced_at' => now(),
        ]);
        
        Log::info('Inventory updated', [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'old_inventory' => $oldInventory,
            'new_inventory' => $available,
            'change' => $available - $oldInventory
        ]);
        
        // Check if threshold was crossed
        $thresholdResult = $this->checkInventoryThresholds($product, $oldInventory, $available);
        
        return [
            'product_updated' => true,
            'inventory_changed' => ($oldInventory !== $available),
            'threshold_check' => $thresholdResult
        ];
    }
    
    /**
     * Process product update payload
     */
    private function processProductUpdate(Shop $shop, array $data): void
    {
        $productId = $data['id'] ?? null;
        
        if (!$productId) {
            throw new \Exception('Missing product ID in webhook');
        }
        
        // Extract numeric ID from GraphQL ID
        $numericId = $this->extractIdFromGid($productId);
        
        // Find all variants of this product
        $products = Product::where('shop_id', $shop->id)
            ->where('shopify_product_id', $numericId)
            ->get();
        
        if ($products->isEmpty()) {
            Log::warning('Product not found for update', [
                'product_id' => $numericId,
                'shop_id' => $shop->id
            ]);
            return;
        }
        
        // Update basic product info
        foreach ($products as $product) {
            $product->update([
                'title' => $data['title'] ?? $product->title,
                'product_type' => $data['product_type'] ?? $product->product_type,
                'vendor' => $data['vendor'] ?? $product->vendor,
                'status' => $data['status'] ?? $product->status,
                'last_synced_at' => now(),
            ]);
        }
        
        Log::info('Product info updated', [
            'product_id' => $numericId,
            'variants_updated' => $products->count(),
            'new_title' => $data['title'] ?? 'not_changed'
        ]);
    }
    
    /**
     * Check if inventory crossed any threshold
     */
    private function checkInventoryThresholds(Product $product, int $oldInventory, int $newInventory): array
    {
        $thresholds = [20, 15, 10, 5, 4, 3, 2, 1];
        $triggered = [];
        
        foreach ($thresholds as $threshold) {
            // Check if we crossed the threshold (going from above to below)
            if ($oldInventory > $threshold && $newInventory <= $threshold) {
                $triggered[] = $threshold;
                
                Log::info('INVENTORY THRESHOLD TRIGGERED', [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'threshold' => $threshold,
                    'old_inventory' => $oldInventory,
                    'new_inventory' => $newInventory
                ]);
                
                // Dispatch alert job
                $this->dispatchAlert($product, $threshold, $oldInventory, $newInventory);
            }
            
            // Check if we crossed the threshold (going from below to above)
            if ($oldInventory <= $threshold && $newInventory > $threshold) {
                Log::info('Inventory recovered above threshold', [
                    'product_id' => $product->id,
                    'threshold' => $threshold,
                    'old_inventory' => $oldInventory,
                    'new_inventory' => $newInventory
                ]);
            }
        }
        
        return [
            'thresholds_triggered' => $triggered,
            'count' => count($triggered)
        ];
    }
    
    /**
     * Dispatch alert job
     */
    private function dispatchAlert(Product $product, int $threshold, int $oldInventory, int $newInventory): void
    {
        // We'll create this job in next step
        Log::info('ALERT DISPATCHED (Job would be queued here)', [
            'product' => $product->title,
            'threshold' => $threshold,
            'inventory' => $newInventory
        ]);
        
        // Temporary: Log alert instead of emailing
        $this->logAlert($product, $threshold, $oldInventory, $newInventory);
    }
    
    /**
     * Temporary: Log alert instead of emailing
     */
    private function logAlert(Product $product, int $threshold, int $oldInventory, int $newInventory): void
    {
        $alertLog = storage_path('logs/alerts.log');
        $message = sprintf(
            "[%s] ALERT: Product '%s' (SKU: %s) inventory crossed threshold %s. " .
            "Changed from %s to %s units. Store: %s\n",
            now()->toDateTimeString(),
            $product->title,
            $product->sku,
            $threshold,
            $oldInventory,
            $newInventory,
            $product->shop->name
        );
        
        file_put_contents($alertLog, $message, FILE_APPEND);
        
        Log::info('Alert logged to file', [
            'product' => $product->title,
            'threshold' => $threshold
        ]);
    }
    
    /**
     * Extract numeric ID from Shopify GraphQL ID
     */
    private function extractIdFromGid(string $gid): int
    {
        $parts = explode('/', $gid);
        return (int) end($parts);
    }
    
    /**
     * Test endpoint to verify webhook setup
     */
}