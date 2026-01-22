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
    ) {
    }

    public function handle(): void
    {
        set_time_limit(0);
        Log::info('=== STARTING PRODUCT SYNC (REST) ===', ['shop' => $this->shop->shopify_domain]);

        try {
            $restService = $this->shop->getRestService();
            $sinceId = 0;
            $hasMore = true;
            $syncedCount = 0;
            $skippedCount = 0;

            while ($hasMore) {
                Log::info("Fetching products since ID {$sinceId}");

                $previousSinceId = $sinceId;

                // Fetch products using REST Service with since_id
                $products = $restService->getProducts(250, $sinceId);

                if (empty($products)) {
                    Log::info('No more products to fetch');
                    $hasMore = false;
                    break;
                }

                foreach ($products as $productData) {
                    $result = $this->syncProductData($productData);

                    if ($result['synced'] > 0) {
                        $syncedCount += $result['synced'];
                    }
                    $skippedCount += $result['skipped'];

                    // Track max ID for next page
                    $sinceId = max($sinceId, $productData['id']);
                }

                // Safety break: if sinceId didn't advance, we're stuck
                if ($sinceId <= $previousSinceId) {
                    Log::warning('Sync stuck: sinceId did not progress', ['sinceId' => $sinceId]);
                    $hasMore = false;
                    break;
                }

                sleep(1); // Rate limiting
            }

            // Update shop's last sync time
            $this->shop->update(['last_synced_at' => now()]);

            Log::info('=== PRODUCT SYNC COMPLETED (REST) ===', [
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

    private function syncProductData(array $product): array
    {
        $variants = $product['variants'] ?? [];
        $synced = 0;
        $skipped = 0;

        // Skip if no variants
        if (empty($variants)) {
            Log::debug('Skipping product - no variants', ['title' => $product['title']]);
            return ['synced' => 0, 'skipped' => 1];
        }

        foreach ($variants as $variant) {

            // Check if we should skip this variant
            if ($this->shouldSkipVariant($variant, $product)) {
                $skipped++;
                continue;
            }

            // Extract size from options
            $size = $this->extractSize($variant, $product);

            // Prepare data with defaults for empty fields
            $productData = [
                'shop_id' => $this->shop->id,
                'shopify_variant_id' => $variant['id'],
                'shopify_product_id' => $product['id'],
                'title' => $product['title'] ?? 'Unknown Product',
                'handle' => $product['handle'] ?? null,
                'sku' => $variant['sku'] ?? 'N/A',
                'size' => $size,
                // REST API field is 'inventory_quantity'
                'current_inventory' => $variant['inventory_quantity'] ?? 0,
                // REST API field is 'inventory_item_id'
                'inventory_item_id' => $variant['inventory_item_id'] ?? null,
                'product_type' => $product['product_type'] ?? 'Uncategorized',
                'vendor' => $product['vendor'] ?? 'Unknown',
                'status' => $product['status'] ?? 'active',
                'price' => $this->parsePrice($variant['price'] ?? null),
                'compare_at_price' => $this->parsePrice($variant['compare_at_price'] ?? null),
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
        }

        return ['synced' => $synced, 'skipped' => $skipped];
    }

    private function shouldSkipVariant(array $variant, array $product): bool
    {
        // Skip if SKU is empty or null
        // Note: REST API will have 'sku' key even if empty string
        if (empty($variant['sku'])) {
            Log::debug('Skipping variant - empty SKU (REST)', [
                'product_title' => $product['title'],
                'variant_id' => $variant['id']
            ]);
            return true;
        }

        return false;
    }

    private function extractSize(array $variant, array $product): ?string
    {
        $options = $product['options'] ?? [];
        $sizeOptions = ['size', 'Size', 'SIZE', 'taglia', 'talle', 'taille', 'maß'];

        foreach ($options as $index => $option) {
            if (in_array($option['name'] ?? '', $sizeOptions)) {
                $optionKey = 'option' . ($index + 1);
                return $variant[$optionKey] ?? null;
            }
        }

        // Fallback: If only one option exists and it looks like a size
        if (count($options) === 1 && !empty($variant['option1'])) {
            return $variant['option1'];
        }

        return null;
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

    public function failed(\Throwable $exception): void
    {
        Log::error('Product sync job failed', [
            'shop_id' => $this->shop->id,
            'error' => $exception->getMessage()
        ]);
    }
}