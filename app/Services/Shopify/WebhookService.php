<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Log;

class WebhookService extends RestService
{
    /**
     * Register all required webhooks for the single shop
     */
    public function registerWebhooksForSingleShop(): array
    {
        $webhooks = [
            [
                'topic' => 'inventory_levels/update',
                'address' => $this->getWebhookUrl('inventory-update'),
                'format' => 'json'
            ],
            [
                'topic' => 'products/delete',
                'address' => $this->getWebhookUrl('product-delete'),
                'format' => 'json'
            ]
        ];

        $results = [];

        foreach ($webhooks as $webhook) {
            try {
                $result = $this->registerSingleWebhook($webhook);
                $results[] = [
                    'topic' => $webhook['topic'],
                    'success' => true,
                    'data' => $result
                ];

                Log::info('Webhook registered', [
                    'topic' => $webhook['topic'],
                    'address' => $webhook['address']
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to register webhook', [
                    'topic' => $webhook['topic'],
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'topic' => $webhook['topic'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Register a single webhook
     */
    private function registerSingleWebhook(array $webhook): array
    {
        $this->rateLimitCheck('rest');

        $response = $this->makeRequest('post', 'admin/api/2024-01/webhooks.json', [
            'webhook' => $webhook
        ]);

        return $response['webhook'] ?? $response;
    }

    /**
     * Get webhook URL for local development
     */
    private function getWebhookUrl(string $type): string
    {
        $baseUrl = config('services.shopify.ngrok_url') ?: config('app.url');

        if (!$baseUrl) {
            $baseUrl = 'http://localhost';
        }

        $url = rtrim($baseUrl, '/') . '/webhooks/' . $type;

        Log::info('Webhook URL generated', [
            'base_url' => $baseUrl,
            'webhook_url' => $url,
            'type' => $type
        ]);

        return $url;
    }

    /**
     * List existing webhooks
     */
    public function listWebhooks(): array
    {
        $this->rateLimitCheck('rest');

        $response = $this->makeRequest('get', 'admin/api/2024-01/webhooks.json');
        return $response['webhooks'] ?? [];
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(int $webhookId): bool
    {
        $this->rateLimitCheck('rest');

        $this->makeRequest('delete', "admin/api/2024-01/webhooks/{$webhookId}.json");
        return true;
    }
}