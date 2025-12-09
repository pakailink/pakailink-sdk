<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PakaiLink Environment
    |--------------------------------------------------------------------------
    |
    | This value determines which PakaiLink environment to use.
    | Options: 'sandbox', 'production'
    |
    */
    'env' => env('PAKAILINK_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | PakaiLink API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for PakaiLink API endpoints.
    | Sandbox: https://sandbox.pakaidonk.id
    | Production: https://api.pakaidonk.id
    |
    */
    'base_url' => env('PAKAILINK_BASE_URL', 'https://rising-dev.pakailink.id'),

    /*
    |--------------------------------------------------------------------------
    | PakaiLink Credentials
    |--------------------------------------------------------------------------
    |
    | Your PakaiLink merchant credentials obtained from the dashboard.
    |
    */
    'credentials' => [
        'client_id' => env('PAKAILINK_CLIENT_ID'),
        'client_secret' => env('PAKAILINK_CLIENT_SECRET'),
        'partner_id' => env('PAKAILINK_PARTNER_ID'),
        'merchant_id' => env('PAKAILINK_MERCHANT_ID'),
        'channel_id' => env('PAKAILINK_CHANNEL_ID'),
        'account_no' => env('PAKAILINK_ACCOUNT_NO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RSA Key Paths
    |--------------------------------------------------------------------------
    |
    | Paths to your RSA private and public keys for signature generation.
    | Keys should be stored outside the web root for security.
    |
    */
    'keys' => [
        'private_key_path' => base_path(env('PAKAILINK_PRIVATE_KEY_PATH', 'storage/keys/pakailink_private.pem')),
        'public_key_path' => base_path(env('PAKAILINK_PUBLIC_KEY_PATH', 'storage/keys/pakailink_public.pem')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling PakaiLink webhook callbacks.
    |
    */
    'callbacks' => [
        'enabled' => env('PAKAILINK_CALLBACKS_ENABLED', true),
        'prefix' => env('PAKAILINK_CALLBACKS_PREFIX', 'api/pakailink/callbacks'),
        'base_url' => env('PAKAILINK_CALLBACK_BASE_URL', env('APP_URL').'/api/pakailink/callbacks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Timeout, retry, and other API-related configuration.
    |
    */
    'timeout' => env('PAKAILINK_TIMEOUT', 30),
    'retry_times' => env('PAKAILINK_RETRY_TIMES', 3),
    'retry_delay' => env('PAKAILINK_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Token cache configuration. Tokens expire in 15 minutes (900 seconds).
    | We cache for 14 minutes (840 seconds) to ensure fresh tokens.
    |
    */
    'cache' => [
        'token_ttl' => 840, // 14 minutes in seconds
        'token_key' => 'pakailink:access_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for debugging purposes.
    |
    */
    'logging' => [
        'enabled' => env('PAKAILINK_LOGGING_ENABLED', true),
        'channel' => env('PAKAILINK_LOGGING_CHANNEL', 'pakailink'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific payment methods globally.
    |
    */
    'features' => [
        'virtual_account_enabled' => env('PAKAILINK_VA_ENABLED', true),
        'qris_enabled' => env('PAKAILINK_QRIS_ENABLED', true),
        'ewallet_enabled' => env('PAKAILINK_EWALLET_ENABLED', true),
        'retail_enabled' => env('PAKAILINK_RETAIL_ENABLED', false),
        'transfer_enabled' => env('PAKAILINK_TRANSFER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | PakaiLink API endpoint URLs. When PakaiLink updates their API,
    | you can override these endpoints via environment variables or by
    | publishing this config file: php artisan vendor:publish --tag=pakailink
    |
    */
    'endpoints' => [
        'auth' => [
            'b2b_token' => env('PAKAILINK_ENDPOINT_B2B_TOKEN', '/snap/v1.0/access-token/b2b'),
        ],

        'virtual_account' => [
            'create' => env('PAKAILINK_ENDPOINT_VA_CREATE', '/snap/v1.0/transfer-va/create-va'),
            'inquiry_status' => env('PAKAILINK_ENDPOINT_VA_INQUIRY', '/snap/v1.0/transfer-va/create-va-status'),
        ],

        'emoney' => [
            'create_payment' => env('PAKAILINK_ENDPOINT_EMONEY_CREATE', '/snap/v1.0/payment/emoney'),
            'inquiry_status' => env('PAKAILINK_ENDPOINT_EMONEY_INQUIRY', '/snap/v1.0/payment/emoney-status'),
        ],

        'qris' => [
            'generate' => env('PAKAILINK_ENDPOINT_QRIS_GENERATE', '/snap/v1.0/qr/qr-mpm-generate'),
            'inquiry' => env('PAKAILINK_ENDPOINT_QRIS_INQUIRY', '/snap/v1.0/qr/qr-mpm-status'),
        ],

        'transfer' => [
            'inquiry' => env('PAKAILINK_ENDPOINT_TRANSFER_INQUIRY', '/snap/v1.0/emoney/bank-account-inquiry'),
            'transfer_to_bank' => env('PAKAILINK_ENDPOINT_TRANSFER_BANK', '/snap/v1.0/emoney/transfer-bank'),
            'inquiry_status' => env('PAKAILINK_ENDPOINT_TRANSFER_STATUS', '/snap/v1.0/emoney/transfer-bank/status'),
        ],

        'balance' => [
            'inquiry' => env('PAKAILINK_ENDPOINT_BALANCE_INQUIRY', '/snap/v1.0/balance-inquiry'),
            'history' => env('PAKAILINK_ENDPOINT_BALANCE_HISTORY', '/snap/v1.0/balance-history'),
        ],

        'settlement' => [
            'inquiry_status' => env('PAKAILINK_ENDPOINT_SETTLEMENT_INQUIRY', '/api/v1.0/settlement/inquiry-status'),
        ],

        'retail' => [
            'create_payment' => env('PAKAILINK_ENDPOINT_RETAIL_CREATE', '/api/v1.0/retail/payment'),
            'inquiry_status' => env('PAKAILINK_ENDPOINT_RETAIL_INQUIRY', '/api/v1.0/retail/status'),
        ],

        'topup' => [
            'inquiry' => env('PAKAILINK_ENDPOINT_TOPUP_INQUIRY', '/api/v1.0/customer-topup/inquiry'),
            'payment' => env('PAKAILINK_ENDPOINT_TOPUP_PAYMENT', '/api/v1.0/customer-topup/payment'),
            'inquiry_status' => env('PAKAILINK_ENDPOINT_TOPUP_STATUS', '/api/v1.0/customer-topup/status'),
        ],

        'product' => [
            'list' => env('PAKAILINK_ENDPOINT_PRODUCT_LIST', '/api/v1.0/product/list'),
        ],
    ],
];
