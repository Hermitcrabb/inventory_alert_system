<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Shopify\WebhookService;
use Illuminate\Support\Facades\Log;

class SyncShopifyWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:webhooks-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-register all Shopify webhooks for the configured single store';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeDomain = config('services.shopify.store_domain');
        $adminToken = config('services.shopify.admin_token');

        if (!$storeDomain || !$adminToken) {
            $this->error('Shopify credentials not configured in .env file.');
            return 1;
        }

        $this->info("Syncing webhooks for store: {$storeDomain}...");

        try {
            $webhookService = new WebhookService();

            // First list existing webhooks to remove old ones if needed
            $existingWebhooks = $webhookService->listWebhooks();
            foreach ($existingWebhooks as $wh) {
                if (str_contains($wh['address'], 'webhooks/')) {
                    $this->line("  Deleting existing webhook: {$wh['topic']} -> {$wh['address']}");
                    $webhookService->deleteWebhook($wh['id']);
                }
            }

            // Register new webhooks
            $results = $webhookService->registerWebhooksForSingleShop();

            foreach ($results as $result) {
                if ($result['success']) {
                    $this->line("  [SUCCESS] Registered: {$result['topic']}");
                } else {
                    $this->error("  [FAILED]  Registered: {$result['topic']} - {$result['error']}");
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error processing sync: " . $e->getMessage());
            Log::error('Webhook sync command failed', ['error' => $e->getMessage()]);
            return 1;
        }

        $this->info('Webhook sync completed.');
        return 0;
    }
}
