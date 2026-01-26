<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Services\Shopify\RestService;

class SyncShopifyProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;

    public function __construct()
    {
    }

    public function handle(): void
    {
        set_time_limit(0);
        Log::info('=== STARTING PRODUCT SYNC (REST) ===');

        try {
            $restService = new RestService();
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

            Log::info('=== PRODUCT SYNC COMPLETED (REST) ===', [
                'synced_variants' => $syncedCount,
                'skipped_variants' => $skippedCount,
                'total_in_db' => Product::count()
            ]);

        } catch (\Exception $e) {
            Log::error('Product sync failed', [
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

            // Check if we should skip this variant (e.g. empty SKU)
            if ($this->shouldSkipVariant($variant, $product)) {
                $skipped++;
                continue;
            }

            $inventoryItemId = $variant['inventory_item_id'] ?? null;
            $quantity = $variant['inventory_quantity'] ?? 0;

            // LOGIC: Only store if quantity <= 20
            if ($quantity > 20) {
                // If it's already in our DB but now > 20, we must delete it
                if ($inventoryItemId) {
                    $deleted = Product::where('inventory_item_id', $inventoryItemId)->delete();
                    if ($deleted) {
                        Log::info('Product removed during sync (quantity > 20)', ['sku' => $variant['sku'], 'quantity' => $quantity]);
                    }
                }
                $skipped++;
                continue;
            }

            // Prepare data with defaults for empty fields
            $productData = [
                'variant_id' => $variant['id'],
                'product_id' => $product['id'],
                'product_title' => $product['title'] ?? 'Unknown Product',
                'variant_title' => $variant['title'] ?? 'Default Title',
                'sku' => $variant['sku'],
                'quantity' => $quantity,
                'inventory_item_id' => $inventoryItemId,
                'last_synced_at' => now(),
            ];

            // Save product
            Product::updateOrCreate(
                [
                    'inventory_item_id' => $productData['inventory_item_id'],
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
        if (empty($variant['sku'])) {
            Log::debug('Skipping variant - empty SKU (REST)', [
                'product_title' => $product['title'],
                'variant_id' => $variant['id']
            ]);
            return true;
        }

        return false;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Product sync job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}