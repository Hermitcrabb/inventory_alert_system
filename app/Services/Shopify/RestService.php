<?php

namespace App\Services\Shopify;

class RestService extends BaseShopifyService
{
    public function registerWebhooks(array $webhooks): array
    {
        $this->rateLimitCheck('rest');

        $results = [];
        foreach ($webhooks as $webhook) {
            $response = $this->makeRequest('post', 'admin/api/2024-01/webhooks.json', [
                'webhook' => $webhook
            ]);

            $results[] = $response['webhook'] ?? $response;
        }

        return $results;
    }

    public function getProducts(int $limit = 250, int $sinceId = 0): array
    {
        $this->rateLimitCheck('rest');

        $endpoint = "admin/api/2024-01/products.json?limit={$limit}&since_id={$sinceId}";
        $response = $this->makeRequest('get', $endpoint);

        return $response['products'] ?? [];
    }

    public function getLocations(): array
    {
        $this->rateLimitCheck('rest');

        $response = $this->makeRequest('get', 'admin/api/2024-01/locations.json');
        return $response['locations'] ?? [];
    }

    public function deleteWebhook(int $webhookId): bool
    {
        $this->rateLimitCheck('rest');

        $endpoint = "admin/api/2024-01/webhooks/{$webhookId}.json";
        $this->makeRequest('delete', $endpoint);

        return true;
    }

    /**
     * Get single inventory item via REST
     */
    public function getInventoryLevel(int $inventoryItemId): array
    {
        $this->rateLimitCheck('rest');

        // Note: Using get inventory_items endpoint
        $endpoint = "admin/api/2024-01/inventory_items/{$inventoryItemId}.json";
        $response = $this->makeRequest('get', $endpoint);

        return $response['inventory_item'] ?? [];
    }

}