<?php

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

abstract class BaseShopifyService
{
    protected string $shopDomain;
    protected string $accessToken;

    public function __construct(string $shopDomain = null, string $accessToken = null)
    {
        $this->shopDomain = $shopDomain ?: config('services.shopify.store_domain');
        $this->accessToken = $accessToken ?: config('services.shopify.admin_token');

        if (!$this->shopDomain || !$this->accessToken) {
            Log::error('Shopify Service initialized without credentials');
        }
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $url = "https://{$this->shopDomain}/{$endpoint}";

        try {
            $response = Http::withHeaders([
                        'X-Shopify-Access-Token' => $this->accessToken,
                        'Content-Type' => 'application/json',
                    ])->{$method}($url, $data);

            if ($response->failed()) {
                Log::error('Shopify API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception("Shopify API Error: {$response->body()}");
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Shopify API exception', [
                'error' => $e->getMessage(),
                'shop' => $this->shopDomain,
            ]);
            throw $e;
        }
    }

    protected function rateLimitCheck(string $apiType = 'rest'): void
    {
        $key = "shopify_rate_limit:{$this->shopDomain}:{$apiType}";
        $limit = config("shopify.rate_limit.{$apiType}", 40);

        $count = Cache::get($key, 0);

        if ($count >= $limit) {
            sleep(config('shopify.rate_limit.retry_after', 2));
        }

        Cache::put($key, $count + 1, 60);
    }
}