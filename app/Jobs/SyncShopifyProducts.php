<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Shop;
use App\Models\Product;

class SyncShopifyProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600;
    public $tries = 3;
    
    public function __construct(
        private Shop $shop
    ) {}
    
    public function handle(): void
    {
        Log::info('=== STARTING PRODUCT SYNC ===', ['shop' => $this->shop->shopify_domain]);
        
        try {
            $graphQLService = $this->shop->getGraphQLService();
            $cursor = null;
            $hasNextPage = true;
            $syncedCount = 0;
            $skippedCount = 0;
            $page = 1;
            
            while ($hasNextPage) {
                Log::info("Fetching page {$page}", ['cursor' => $cursor]);
                
                $response = $graphQLService->getProductsWithInventory($cursor);
                
                if (empty($response['edges'])) {
                    Log::info('No more products to fetch');
                    break;
                }
                
                foreach ($response['edges'] as $edge) {
                    $productNode = $edge['node'];
                    $result = $this->syncProductData($productNode);
                    
                    if ($result['synced'] > 0) {
                        $syncedCount += $result['synced'];
                    }
                    $skippedCount += $result['skipped'];
                }
                
                $hasNextPage = $response['pageInfo']['hasNextPage'] ?? false;
                $cursor = $response['pageInfo']['endCursor'] ?? null;
                $page++;
                
                sleep(1); // Rate limiting
            }
            
            // Update shop's last sync time
            $this->shop->update(['last_synced_at' => now()]);
            
            Log::info('=== PRODUCT SYNC COMPLETED ===', [
                'shop' => $this->shop->shopify_domain,
                'synced_variants' => $syncedCount,
                'skipped_variants' => $skippedCount,
                'total_in_db' => Product::where('shop_id', $this->shop->id)->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Product sync failed', [
                'shop' => $this->shop->shopify_domain,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    private function syncProductData(array $productNode): array
    {
        $variants = $productNode['variants']['edges'] ?? [];
        $synced = 0;
        $skipped = 0;
        
        // Skip if no variants
        if (empty($variants)) {
            Log::debug('Skipping product - no variants', ['title' => $productNode['title']]);
            return ['synced' => 0, 'skipped' => 1];
        }
        
        foreach ($variants as $variantEdge) {
            $variant = $variantEdge['node'];
            
            // Check if we should skip this variant
            if ($this->shouldSkipVariant($variant, $productNode)) {
                $skipped++;
                continue;
            }
            
            // Prepare data with defaults for empty fields
            $productData = [
                'shop_id' => $this->shop->id,
                'shopify_variant_id' => $this->extractId($variant['id']),
                'shopify_product_id' => $this->extractId($productNode['id']),
                'title' => $productNode['title'] ?? 'Unknown Product',
                'handle' => $productNode['handle'] ?? null,
                'sku' => $variant['sku'] ?? 'N/A',
                'current_inventory' => $variant['inventoryQuantity'] ?? 0,
                'inventory_item_id' => isset($variant['inventoryItem']['id']) ? 
                    $this->extractId($variant['inventoryItem']['id']) : null,
                'product_type' => $productNode['productType'] ?? 'Uncategorized',
                'vendor' => $productNode['vendor'] ?? 'Unknown',
                'status' => $productNode['status'] ?? 'active',
                'price' => $this->parsePrice($variant['price'] ?? null),
                'compare_at_price' => $this->parsePrice($variant['compareAtPrice'] ?? null),
                'last_synced_at' => now(),
            ];
            
            // Save product
            Product::updateOrCreate(
                [
                    'shop_id' => $productData['shop_id'],
                    'shopify_variant_id' => $productData['shopify_variant_id'],
                ],
                $productData
            );
            
            $synced++;
            Log::debug('Synced variant', [
                'title' => $productData['title'],
                'sku' => $productData['sku'],
                'inventory' => $productData['current_inventory']
            ]);
        }
        
        return ['synced' => $synced, 'skipped' => $skipped];
    }
    
    private function shouldSkipVariant(array $variant, array $productNode): bool
    {
        // Skip if SKU is empty or null (you can adjust this rule)
        if (empty($variant['sku']) || $variant['sku'] === null) {
            Log::debug('Skipping variant - empty SKU', [
                'product_title' => $productNode['title'],
                'variant_id' => $this->extractId($variant['id'])
            ]);
            return true;
        }
        
        // Skip if inventory is not set (optional rule)
        if (!isset($variant['inventoryQuantity'])) {
            Log::debug('Skipping variant - no inventory quantity', [
                'product_title' => $productNode['title'],
                'sku' => $variant['sku']
            ]);
            return true;
        }
        
        // Skip if price is not set (optional rule)
        if (empty($variant['price'])) {
            Log::debug('Skipping variant - no price', [
                'product_title' => $productNode['title'],
                'sku' => $variant['sku']
            ]);
            return true;
        }
        
        return false;
    }
    
    private function parsePrice($price)
    {
        if (empty($price) || $price === null) {
            return null;
        }
        
        // Remove currency symbols and convert to float
        $price = str_replace(['$', '€', '£', '¥'], '', $price);
        $price = trim($price);
        
        return is_numeric($price) ? (float) $price : null;
    }
    
    private function extractId(string $gid): int
    {
        $parts = explode('/', $gid);
        return (int) end($parts);
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('Product sync job failed', [
            'shop_id' => $this->shop->id,
            'error' => $exception->getMessage()
        ]);
    }
}