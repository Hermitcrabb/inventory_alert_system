<?php

namespace App\Services\Shopify;
use Illuminate\Support\Facades\Log;

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
    
    public function getProducts(int $limit = 250, int $page = 1): array
    {
        $this->rateLimitCheck('rest');
        
        $endpoint = "admin/api/2024-01/products.json?limit={$limit}&page={$page}";
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

    public function getShopInfo(): array
    {
        $this->rateLimitCheck('rest');
    
        $response = $this->makeRequest('get', 'admin/api/2024-01/shop.json');
        return $response['shop'] ?? [];
    }

}