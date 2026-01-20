<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;
use App\Services\Shopify\WebhookService;

class ShopifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RestService::class, function ($app) {
            $shopDomain = config('services.shopify.store_domain');
            $accessToken = config('services.shopify.admin_token');
            
            if (!$shopDomain || !$accessToken) {
                throw new \RuntimeException('Shopify credentials not configured in services config.');
            }
            
            return new RestService($shopDomain, $accessToken);
        });
        
        $this->app->bind(GraphQLService::class, function ($app) {
            $shopDomain = config('services.shopify.store_domain');
            $accessToken = config('services.shopify.admin_token');
            
            if (!$shopDomain || !$accessToken) {
                throw new \RuntimeException('Shopify credentials not configured in services config.');
            }
            
            return new GraphQLService($shopDomain, $accessToken);
        });
        
        $this->app->bind(WebhookService::class, function ($app) {
            $shopDomain = config('services.shopify.store_domain');
            $accessToken = config('services.shopify.admin_token');
            
            if (!$shopDomain || !$accessToken) {
                throw new \RuntimeException('Shopify credentials not configured in services config.');
            }
            
            return new WebhookService($shopDomain, $accessToken);
        });
    }
    
    public function boot(): void
    {
        //
    }
}