<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);
        
        $socialite->extend('shopify', function ($app) use ($socialite) {
            $config = $app['config']['services.shopify'];
            
            return $socialite->buildProvider(
                \SocialiteProviders\Shopify\Provider::class,
                $config
            );
        });
    }
}
