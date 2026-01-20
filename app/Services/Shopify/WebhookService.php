<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Log;
use App\Models\Shop;

class WebhookService extends RestService
{
    /**
     * Register all required webhooks for a shop
     * Override parent method with different signature
     */
    public function registerWebhooksForShop(Shop $shop): array
    {
        $webhooks = [
            [
                'topic' => 'inventory_levels/update',
                'address' => $this->getWebhookUrl('inventory-update'),
                'format' => 'json'
            ],
            [
                'topic' => 'products/update',
                'address' => $this->getWebhookUrl('product-update'),
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
                    'shop' => $shop->shopify_domain,
                    'topic' => $webhook['topic'],
                    'address' => $webhook['address']
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to register webhook', [
                    'shop' => $shop->shopify_domain,
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
     * Keep parent method compatibility
     */
    public function registerWebhooks(array $webhooks): array
    {
        return parent::registerWebhooks($webhooks);
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
        // Priority: 1. NGROK_URL from .env, 2. APP_URL
        $baseUrl = config('services.shopify.ngrok_url') ?: config('app.url');
        
        // Default to localhost if nothing set
        if (!$baseUrl) {
            $baseUrl = 'http://localhost';
        }
        
        // Clean up URL and add project path if needed
        $baseUrl = rtrim($baseUrl, '/');
        
        // Check if base URL already includes the project path
        // If not, and we're in a subdirectory, add it
        $projectPath = '/inventory_alert_system/public';
        if (strpos($baseUrl, $projectPath) === false && strpos(url(''), $projectPath) !== false) {
            $baseUrl .= $projectPath;
        }
        
        $url = $baseUrl . '/webhooks/' . $type;
        
        Log::info('Webhook URL generated', [
            'base_url' => $baseUrl,
            'webhook_url' => $url,
            'type' => $type,
            'full_url_used' => url('/webhooks/' . $type)
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