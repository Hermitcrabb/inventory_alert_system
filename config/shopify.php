<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'api_version' => env('SHOPIFY_API_VERSION', '2024-01'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
    'scopes' => [
        'read_products',
        'read_inventory',
        'write_inventory',
        'read_locations',
        'read_orders',
        'write_products',
    ],
    'redirect_uri' => env('SHOPIFY_REDIRECT_URI', '/shopify/callback'),

    // Rate limiting
    'rate_limit' => [
        'rest' => 120, // requests per minute
        'graphql' => 1000, // cost per minute
        'retry_after' => 2, // seconds
    ],
];