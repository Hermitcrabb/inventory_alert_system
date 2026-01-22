<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Shopify\RestService;
use App\Services\Shopify\GraphQLService;

class ShopifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RestService::class, function ($app, $parameters) {
            return new RestService($parameters['shopDomain'], $parameters['accessToken']);
        });
        
        $this->app->bind(GraphQLService::class, function ($app, $parameters) {
            return new GraphQLService($parameters['shopDomain'], $parameters['accessToken']);
        });
    }
    
    public function boot(): void
    {
        //
    }
}