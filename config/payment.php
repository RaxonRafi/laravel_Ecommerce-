<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | Used when the customer does not choose one. Cash on delivery works without
    | any credentials, so the store can trade before a gateway is signed up for.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'cod'),

    'currency' => env('PAYMENT_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | A gateway is only offered at checkout when `enabled` is true and its
    | credentials are present. The SSLCommerz and bKash entries are scaffolding:
    | fill in the credentials, flip `enabled`, and implement the corresponding
    | gateway class. No order code needs to change.
    |
    */

    'gateways' => [

        'cod' => [
            'enabled' => env('PAYMENT_COD_ENABLED', true),
            'label' => 'Cash on Delivery',
        ],

        'sslcommerz' => [
            'enabled' => env('PAYMENT_SSLCOMMERZ_ENABLED', false),
            'label' => 'SSLCommerz',
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),

            // Two entirely separate environments with separate credentials.
            // securepay is LIVE — it is not the sandbox, despite how often it is
            // quoted as one.
            'sandbox_url' => 'https://sandbox.sslcommerz.com',
            'live_url' => 'https://securepay.sslcommerz.com',
        ],

        'bkash' => [
            'enabled' => env('PAYMENT_BKASH_ENABLED', false),
            'label' => 'bKash',
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],

    ],

];
