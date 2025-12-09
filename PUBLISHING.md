# Publishing PakaiLink Configuration

This guide explains how to publish and customize the PakaiLink configuration in your Laravel application.

## Quick Start

### Publish Configuration File

```bash
php artisan vendor:publish --tag=pakailink
```

This creates `config/pakailink.php` in your main application with all settings.

## What Gets Published

The published `config/pakailink.php` includes:

1. **Credentials** - API keys and secrets
2. **RSA Keys** - Path configuration
3. **Callbacks** - Webhook settings
4. **API Settings** - Timeout and retry configuration
5. **Cache Settings** - Token caching
6. **Logging** - Log channel configuration
7. **Feature Flags** - Enable/disable payment methods
8. **Endpoints** - All 24 API endpoint URLs ⭐

## Customizing Endpoints

After publishing, you can customize endpoints in two ways:

### Method 1: Edit config/pakailink.php (Recommended)

```php
// config/pakailink.php

'endpoints' => [
    'virtual_account' => [
        'create' => '/api/v2.0/virtual-account/create', // Updated
        'inquiry_status' => '/api/v2.0/virtual-account/status', // Updated
        'update' => '/api/v1.0/transfer-va/create-va',
        'delete' => '/api/v1.0/transfer-va/delete-va',
    ],

    'qris' => [
        'generate' => '/api/v2.0/qris/generate', // Updated
        'inquiry' => '/api/v1.0/qr/qr-mpm-query',
        'refund' => '/api/v1.0/qr/qr-mpm-refund',
    ],

    // ... other endpoints
],
```

### Method 2: Use Environment Variables

```env
# .env - Override specific endpoints
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/virtual-account/create
PAKAILINK_ENDPOINT_VA_INQUIRY=/api/v2.0/virtual-account/status
PAKAILINK_ENDPOINT_QRIS_GENERATE=/api/v2.0/qris/generate
```

## When to Publish

### Publish if you need to:
- ✅ Customize API endpoints
- ✅ Change default timeout/retry values
- ✅ Modify callback URL structure
- ✅ Adjust cache TTL
- ✅ Disable specific payment methods
- ✅ Change logging configuration

### Don't publish if:
- ❌ You only need to set credentials (use .env instead)
- ❌ Default settings work for you
- ❌ You just want to override one or two values (use .env)

## Publishing Tags

The package supports the following publishing tags:

```bash
# Publish everything
php artisan vendor:publish --tag=pakailink

# Publish only config (same as above)
php artisan vendor:publish --tag=pakailink-config
```

## After Publishing

### 1. Clear Config Cache

```bash
php artisan config:clear
```

### 2. Verify Configuration

```bash
php artisan tinker
>>> config('pakailink.endpoints.virtual_account.create')
=> "/api/v2.0/virtual-account/create"
```

### 3. Test Integration

```bash
php artisan test tests/Integration/PakaiLink/
```

## Configuration Priority

The SDK loads configuration in this order (highest to lowest priority):

1. **Published config** (`config/pakailink.php` in your app)
2. **Environment variables** (`.env` file)
3. **Package defaults** (`packages/.../config/pakailink.php`)

### Example:

```php
// Package default
'create' => env('PAKAILINK_ENDPOINT_VA_CREATE', '/api/v1.0/transfer-va/create-va')

// If .env has:
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/va/create
// Result: /api/v2.0/va/create ✓

// If published config has:
'create' => '/api/v3.0/virtual-account/create'
// Result: /api/v3.0/virtual-account/create ✓

// If both exist, published config wins ✓
```

## Full Configuration Structure

After publishing, your `config/pakailink.php` includes:

```php
return [
    'env' => 'sandbox',
    'base_url' => 'https://rising-dev.pakailink.id',

    'credentials' => [
        'client_id' => env('PAKAILINK_CLIENT_ID'),
        'client_secret' => env('PAKAILINK_CLIENT_SECRET'),
        'partner_id' => env('PAKAILINK_PARTNER_ID'),
        'merchant_id' => env('PAKAILINK_MERCHANT_ID'),
        'channel_id' => env('PAKAILINK_CHANNEL_ID'),
    ],

    'keys' => [
        'private_key_path' => base_path('storage/keys/pakailink_private.pem'),
        'public_key_path' => base_path('storage/keys/pakailink_public.pem'),
    ],

    'callbacks' => [
        'enabled' => true,
        'prefix' => 'api/pakailink/callbacks',
        'base_url' => env('APP_URL') . '/api/pakailink/callbacks',
    ],

    'timeout' => 30,
    'retry_times' => 3,
    'retry_delay' => 1000,

    'cache' => [
        'token_ttl' => 840,
        'token_key' => 'pakailink:access_token',
    ],

    'logging' => [
        'enabled' => true,
        'channel' => 'pakailink',
    ],

    'features' => [
        'virtual_account_enabled' => true,
        'qris_enabled' => true,
        'ewallet_enabled' => true,
        'retail_enabled' => false,
        'transfer_enabled' => true,
    ],

    'endpoints' => [
        'auth' => [...],
        'virtual_account' => [...],
        'qris' => [...],
        'emoney' => [...],
        'transfer' => [...],
        'balance' => [...],
        'settlement' => [...],
        'retail' => [...],
        'topup' => [...],
        'product' => [...],
    ],
];
```

## Common Customizations

### Update All VA Endpoints to v2.0

```php
// config/pakailink.php
'endpoints' => [
    'virtual_account' => [
        'create' => '/api/v2.0/va/create',
        'inquiry_status' => '/api/v2.0/va/status',
        'update' => '/api/v2.0/va/update',
        'delete' => '/api/v2.0/va/delete',
    ],
],
```

### Disable Callbacks

```php
// config/pakailink.php
'callbacks' => [
    'enabled' => false, // Disable all callback routes
],
```

### Change Callback URL Prefix

```php
// config/pakailink.php
'callbacks' => [
    'prefix' => 'webhooks/pakailink', // Changed from api/pakailink/callbacks
],
```

### Increase Timeout for Slow Networks

```php
// config/pakailink.php
'timeout' => 60, // Increase from 30 to 60 seconds
'retry_times' => 5, // Increase from 3 to 5 retries
```

## Best Practices

### 1. Keep Credentials in .env

```php
// config/pakailink.php - DON'T hardcode credentials
'credentials' => [
    'client_id' => env('PAKAILINK_CLIENT_ID'), // ✓ Good
    'client_secret' => env('PAKAILINK_CLIENT_SECRET'), // ✓ Good
],

// BAD:
'credentials' => [
    'client_id' => 'client-key-123', // ✗ Don't do this
],
```

### 2. Document Your Changes

```php
// config/pakailink.php
'endpoints' => [
    'virtual_account' => [
        // Updated to v2.0 on 2025-12-01 for new features
        'create' => '/api/v2.0/va/create',
    ],
],
```

### 3. Version Control

```bash
# Commit published config
git add config/pakailink.php
git commit -m "Publish PakaiLink config with custom endpoints"
```

### 4. Test After Changes

```bash
php artisan config:clear
php artisan test tests/Integration/PakaiLink/
```

## Unpublishing

To revert to package defaults:

```bash
# Remove published config
rm config/pakailink.php

# Clear cache
php artisan config:clear
```

SDK will use package defaults again.

## Support

### Check Current Configuration

```bash
php artisan tinker
>>> config('pakailink')
>>> config('pakailink.endpoints')
```

### Verify Endpoints

```bash
# Check specific endpoint
>>> config('pakailink.endpoints.virtual_account.create')
=> "/api/v1.0/transfer-va/create-va"
```

### Re-publish After Package Update

```bash
# Force re-publish
php artisan vendor:publish --tag=pakailink --force
```

---

**Publish Command:** `php artisan vendor:publish --tag=pakailink`
**Config File:** `config/pakailink.php`
**Total Settings:** 100+ configuration options
**Endpoints:** 24 customizable endpoints
