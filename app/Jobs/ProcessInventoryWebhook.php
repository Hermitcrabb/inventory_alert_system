<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Product;
use App\Models\InventoryAlert;
use App\Mail\LowStockAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessInventoryWebhook implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) ($this->data['inventory_item_id'] ?? '');
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public function uniqueFor(): int
    {
        return 1;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inventoryItemId = (string) ($this->data['inventory_item_id'] ?? '');
        $available = (int) ($this->data['available'] ?? 0);
        $locationId = (string) ($this->data['location_id'] ?? '');

        Log::info('--- JOB: PROCESSING INVENTORY UPDATE ---', [
            'item_id' => $inventoryItemId,
            'available' => $available
        ]);

        if (!$inventoryItemId) {
            Log::error('Job failed: Missing inventory_item_id');
            return;
        }

        try {
            // 1. If available > 20, delete from local DB and exit
            if ($available > 20) {
                $deletedCount = Product::where('inventory_item_id', $inventoryItemId)->delete();
                if ($deletedCount > 0) {
                    Log::info('Product quantity > 20, deleted from local DB', ['inventory_item_id' => $inventoryItemId]);
                }
                return;
            }

            // 2. Get details and update/create

            // Get SKU via REST
            $restService = new \App\Services\Shopify\RestService();
            $inventoryItem = $restService->getInventoryLevel((int) $inventoryItemId);
            $sku = $inventoryItem['sku'] ?? null;

            // SKU Skipping Logic: if sku is not available, we skip.
            if (empty($sku)) {
                Log::warning('Skipping product without SKU', ['inventory_item_id' => $inventoryItemId]);
                return;
            }

            // Get Details via GraphQL using SKU
            $graphQLService = new \App\Services\Shopify\GraphQLService();
            $variant = $graphQLService->getVariantBySku($sku);

            if (!$variant) {
                Log::warning('Product details not found via GraphQL for SKU', ['sku' => $sku]);
                $productTitle = 'Unknown Product';
                $variantTitle = 'Unknown Variant';
                $productId = 0;
                $variantId = 0;
            } else {
                $productTitle = $variant['product']['title'] ?? 'Unknown';
                $variantTitle = $variant['title'] ?? 'Default Title';
                $productId = $this->extractIdFromGid($variant['product']['id']);
                $variantId = $this->extractIdFromGid($variant['id']);
            }

            // 3. Update local DB
            $product = Product::updateOrCreate(
                ['inventory_item_id' => $inventoryItemId],
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'product_title' => $productTitle,
                    'variant_title' => $variantTitle,
                    'sku' => $sku,
                    'quantity' => $available,
                    'location_id' => $locationId,
                    'last_synced_at' => now(),
                ]
            );

            // 4. Threshold Check & Mail
            $this->handleThrottledLowStockAlerts($product, $available);

            Log::info('Job completed successfully', ['sku' => $sku, 'quantity' => $available]);

        } catch (\Exception $e) {
            Log::error('Job failed', [
                'error' => $e->getMessage(),
                'item_id' => $inventoryItemId
            ]);
            throw $e;
        }
    }

    private function handleThrottledLowStockAlerts(Product $product, int $available): void
    {
        // one email at 20, one at 10, and then for [1-5] range.
        $thresholdLevel = null;
        $thresholdGroup = null;

        if ($available == 20) {
            $thresholdLevel = 20;
            $thresholdGroup = 20;
        } elseif ($available == 10) {
            $thresholdLevel = 10;
            $thresholdGroup = 10;
        } elseif ($available >= 1 && $available <= 5) {
            $thresholdLevel = $available;
            $thresholdGroup = 5;
        }

        if ($thresholdLevel === null) {
            return;
        }

        // Get current notification state directly from the $product object
        // since we just updated it in handle() but haven't refreshed it yet,
        // we can access the PREVIOUS state if we haven't overwritten it in memory,
        // or we check the DB values if we refetch.
        // However, it's safer to compare against the stored state in the DB.
        $lastNotifiedValue = $product->last_notified_threshold;
        $lastNotifiedGroup = $product->last_notified_threshold_group;

        // Check if we should send alert
        $shouldSendAlert = false;

        if ($lastNotifiedGroup === null) {
            // Never notified before
            $shouldSendAlert = true;
        } elseif ($thresholdGroup !== $lastNotifiedGroup) {
            // Different threshold group (e.g., moved from 20 to 10, or 10 to 1-5)
            $shouldSendAlert = true;
        } elseif ($thresholdGroup === 5 && $lastNotifiedGroup === 5) {
            // For 1-5 range: notify when quantity INCREASES within range
            // e.g., old=2, new=3
            if ($available > $lastNotifiedValue) {
                $shouldSendAlert = true;
            }
        }

        if (!$shouldSendAlert) {
            return;
        }

        // Get all registered users from the database
        $users = \App\Models\User::all();

        if ($users->isEmpty()) {
            Log::warning('No registered users found to notify');
            return;
        }

        // Send Emails
        $sentTo = [];
        $ccEmailsRaw = config('services.shopify.low_stock_cc_emails', '');
        $ccEmails = array_filter(array_map('trim', explode(',', $ccEmailsRaw)));

        foreach ($users as $user) {
            try {
                $mail = Mail::to($user->email);

                if (!empty($ccEmails)) {
                    $mail->cc($ccEmails);
                }

                $mail->send(new LowStockAlert([
                    'product_model' => $product,
                    'available' => $available,
                    'threshold' => $thresholdLevel,
                    'sku' => $product->sku,
                    'product_title' => $product->product_title,
                    'variant_title' => $product->variant_title,
                ]));
                $sentTo[] = $user->email;

                // Log Alert
                \App\Models\AlertLog::log($product->product_id, $user->email, 'low_stock', $available);

            } catch (\Exception $e) {
                Log::error('Failed to send email in job', ['email' => $user->email, 'error' => $e->getMessage()]);
            }
        }

        // Update product state
        $product->update([
            'last_notified_threshold' => $thresholdLevel,
            'last_notified_threshold_group' => $thresholdGroup
        ]);

        // Record Alert
        InventoryAlert::create([
            'product_id' => $product->id,
            'threshold_level' => $thresholdLevel,
            'new_inventory' => $available,
            'recipient_emails' => implode(', ', $sentTo),
            'sent_at' => now(),
        ]);

        Log::info('Throttled alert sent', ['sku' => $product->sku, 'level' => $thresholdLevel, 'group' => $thresholdGroup]);
    }

    private function extractIdFromGid(string $gid): int
    {
        $parts = explode('/', $gid);
        return (int) end($parts);
    }
}
