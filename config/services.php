<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shopify' => [
        'store_domain' => env('SHOPIFY_STORE_DOMAIN'),
        'api_key' => env('SHOPIFY_API_KEY'),
        'api_secret' => env('SHOPIFY_API_SECRET'),
        'admin_token' => env('SHOPIFY_ADMIN_TOKEN'),
        'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
        'notification_emails' => env('SHOPIFY_NOTIFICATION_EMAILS'),
        'low_stock_cc_emails' => env('LOW_STOCK_CC_EMAILS'),
        'scopes' => env('SHOPIFY_SCOPES', 'read_products,read_inventory'),
        'ngrok_url' => env('NGROK_URL'),
    ],

];
