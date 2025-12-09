# PakaiLink SDK - Endpoint Configuration

This document explains how to configure and customize PakaiLink API endpoints.

## Overview

All PakaiLink API endpoints are centralized in a single configuration file: `config/endpoints.php`

This allows you to:
- ✅ Update endpoints when PakaiLink changes their API
- ✅ Override endpoints via environment variables
- ✅ Use different endpoints for sandbox vs production
- ✅ Customize endpoints per environment

## Endpoint Configuration File

Location: `packages/pgpay/pakailink-sdk/config/endpoints.php`

### All Available Endpoints

#### Authentication
```php
'auth' => [
    'b2b_token' => '/snap/v1.0/access-token/b2b',
]
```

#### Virtual Account
```php
'virtual_account' => [
    'create' => '/api/v1.0/transfer-va/create-va',
    'inquiry_status' => '/api/v1.0/transfer-va/status',
    'update' => '/api/v1.0/transfer-va/create-va',
    'delete' => '/api/v1.0/transfer-va/delete-va',
]
```

#### QRIS
```php
'qris' => [
    'generate' => '/api/v1.0/qr/qr-mpm-generate',
    'inquiry' => '/api/v1.0/qr/qr-mpm-query',
    'refund' => '/api/v1.0/qr/qr-mpm-refund',
]
```

#### E-money / E-wallet
```php
'emoney' => [
    'create_payment' => '/api/v1.0/emoney/payment',
    'inquiry_status' => '/api/v1.0/emoney/status',
    'refund' => '/api/v1.0/emoney/refund',
]
```

#### Bank Transfer
```php
'transfer' => [
    'inquiry' => '/api/v1.0/transfer-bank/inquiry',
    'transfer_to_bank' => '/api/v1.0/transfer-bank',
    'inquiry_status' => '/api/v1.0/transfer-bank/status',
    'transfer_to_va' => '/api/v1.0/transfer-va',
]
```

#### Balance & Settlement
```php
'balance' => [
    'inquiry' => '/api/v1.0/balance-inquiry',
    'history' => '/api/v1.0/balance-history',
],

'settlement' => [
    'inquiry_status' => '/api/v1.0/settlement/inquiry-status',
]
```

#### Retail Payments
```php
'retail' => [
    'create_payment' => '/api/v1.0/retail/payment',
    'inquiry_status' => '/api/v1.0/retail/status',
]
```

#### Customer Top-up
```php
'topup' => [
    'inquiry' => '/api/v1.0/customer-topup/inquiry',
    'payment' => '/api/v1.0/customer-topup/payment',
    'inquiry_status' => '/api/v1.0/customer-topup/status',
]
```

## Customizing Endpoints

### Method 1: Environment Variables (Recommended)

Add to your `.env` file to override specific endpoints:

```env
# Override Virtual Account create endpoint
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/virtual-account/create

# Override QRIS generate endpoint
PAKAILINK_ENDPOINT_QRIS_GENERATE=/api/v2.0/qris/generate

# Override E-money payment endpoint
PAKAILINK_ENDPOINT_EMONEY_CREATE=/api/v2.0/ewallet/create-payment
```

### Method 2: Publish Configuration File

```bash
# Publish the endpoints config to your application
php artisan vendor:publish --tag=pakailink-endpoints
```

This creates: `config/pakailink-endpoints.php`

Then edit the file directly:

```php
// config/pakailink-endpoints.php
return [
    'virtual_account' => [
        'create' => '/api/v2.0/virtual-account/create', // Updated
    ],
    // ... other endpoints
];
```

### Method 3: Runtime Configuration

Temporarily override in code (not recommended):

```php
config(['pakailink.endpoints.qris.generate' => '/api/v2.0/qris/new-endpoint']);

$qrisService->generateQris($data); // Uses new endpoint
```

## When PakaiLink Updates Their API

### Scenario: PakaiLink upgrades to API v2.0

**Option A: Update via Environment Variables**

```env
# .env
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/virtual-account/create
PAKAILINK_ENDPOINT_VA_INQUIRY=/api/v2.0/virtual-account/status
PAKAILINK_ENDPOINT_QRIS_GENERATE=/api/v2.0/qris/generate
# ... update other endpoints as needed
```

**Option B: Publish and Edit Config**

```bash
php artisan vendor:publish --tag=pakailink-endpoints
```

Edit `config/pakailink-endpoints.php`:

```php
return [
    'virtual_account' => [
        'create' => '/api/v2.0/virtual-account/create',
        'inquiry_status' => '/api/v2.0/virtual-account/status',
        // ... other endpoints
    ],
];
```

**Option C: Update Package (Recommended for all projects)**

If you manage the SDK package centrally, update:
`packages/pgpay/pakailink-sdk/config/endpoints.php`

All projects using the package will get the update.

## Environment-Specific Endpoints

### Different Endpoints Per Environment

```env
# .env.production
PAKAILINK_BASE_URL=https://api.pakaidonk.id
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.0/transfer-va/create-va

# .env.staging
PAKAILINK_BASE_URL=https://staging.pakaidonk.id
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.1/transfer-va/create-va

# .env.local (sandbox)
PAKAILINK_BASE_URL=https://rising-dev.pakailink.id
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.0/transfer-va/create-va
```

## Accessing Endpoints in Code

### In Service Classes

```php
// Virtual Account Service
$endpoint = config('pakailink.endpoints.virtual_account.create');
$response = $this->client->post($endpoint, $data);
```

### With Fallback Values

```php
$endpoint = config(
    'pakailink.endpoints.qris.generate',
    '/api/v1.0/qr/qr-mpm-generate' // Default fallback
);
```

### Check Current Endpoints

```php
// Get all endpoints
$endpoints = config('pakailink.endpoints');

// Get specific endpoint
$vaCreateEndpoint = config('pakailink.endpoints.virtual_account.create');

// In tinker
php artisan tinker
>>> config('pakailink.endpoints.virtual_account')
```

## Debugging Endpoints

### View All Configured Endpoints

```bash
php artisan tinker
>>> config('pakailink.endpoints')
```

### Test Specific Endpoint

```bash
# Check if endpoint is accessible
curl -X POST https://rising-dev.pakailink.id/api/v1.0/transfer-va/create-va \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Log Endpoint Calls

All endpoint calls are automatically logged:

```
storage/logs/payments/pakailink.log
```

Example log entry:
```
[2025-11-27 18:00:00] pakailink.INFO: Creating Virtual Account
  url: https://rising-dev.pakailink.id/api/v1.0/transfer-va/create-va
  method: POST
```

## Complete Endpoint List

| Category | Endpoint Key | Default Path | Environment Variable |
|----------|--------------|--------------|---------------------|
| **Auth** | `auth.b2b_token` | `/snap/v1.0/access-token/b2b` | `PAKAILINK_ENDPOINT_B2B_TOKEN` |
| **VA** | `virtual_account.create` | `/api/v1.0/transfer-va/create-va` | `PAKAILINK_ENDPOINT_VA_CREATE` |
| **VA** | `virtual_account.inquiry_status` | `/api/v1.0/transfer-va/status` | `PAKAILINK_ENDPOINT_VA_INQUIRY` |
| **VA** | `virtual_account.update` | `/api/v1.0/transfer-va/create-va` | `PAKAILINK_ENDPOINT_VA_UPDATE` |
| **VA** | `virtual_account.delete` | `/api/v1.0/transfer-va/delete-va` | `PAKAILINK_ENDPOINT_VA_DELETE` |
| **QRIS** | `qris.generate` | `/api/v1.0/qr/qr-mpm-generate` | `PAKAILINK_ENDPOINT_QRIS_GENERATE` |
| **QRIS** | `qris.inquiry` | `/api/v1.0/qr/qr-mpm-query` | `PAKAILINK_ENDPOINT_QRIS_INQUIRY` |
| **QRIS** | `qris.refund` | `/api/v1.0/qr/qr-mpm-refund` | `PAKAILINK_ENDPOINT_QRIS_REFUND` |
| **E-money** | `emoney.create_payment` | `/api/v1.0/emoney/payment` | `PAKAILINK_ENDPOINT_EMONEY_CREATE` |
| **E-money** | `emoney.inquiry_status` | `/api/v1.0/emoney/status` | `PAKAILINK_ENDPOINT_EMONEY_INQUIRY` |
| **E-money** | `emoney.refund` | `/api/v1.0/emoney/refund` | `PAKAILINK_ENDPOINT_EMONEY_REFUND` |
| **Transfer** | `transfer.inquiry` | `/api/v1.0/transfer-bank/inquiry` | `PAKAILINK_ENDPOINT_TRANSFER_INQUIRY` |
| **Transfer** | `transfer.transfer_to_bank` | `/api/v1.0/transfer-bank` | `PAKAILINK_ENDPOINT_TRANSFER_BANK` |
| **Transfer** | `transfer.inquiry_status` | `/api/v1.0/transfer-bank/status` | `PAKAILINK_ENDPOINT_TRANSFER_STATUS` |
| **Transfer** | `transfer.transfer_to_va` | `/api/v1.0/transfer-va` | `PAKAILINK_ENDPOINT_TRANSFER_VA` |
| **Balance** | `balance.inquiry` | `/api/v1.0/balance-inquiry` | `PAKAILINK_ENDPOINT_BALANCE_INQUIRY` |
| **Balance** | `balance.history` | `/api/v1.0/balance-history` | `PAKAILINK_ENDPOINT_BALANCE_HISTORY` |
| **Settlement** | `settlement.inquiry_status` | `/api/v1.0/settlement/inquiry-status` | `PAKAILINK_ENDPOINT_SETTLEMENT_INQUIRY` |
| **Retail** | `retail.create_payment` | `/api/v1.0/retail/payment` | `PAKAILINK_ENDPOINT_RETAIL_CREATE` |
| **Retail** | `retail.inquiry_status` | `/api/v1.0/retail/status` | `PAKAILINK_ENDPOINT_RETAIL_INQUIRY` |
| **Top-up** | `topup.inquiry` | `/api/v1.0/customer-topup/inquiry` | `PAKAILINK_ENDPOINT_TOPUP_INQUIRY` |
| **Top-up** | `topup.payment` | `/api/v1.0/customer-topup/payment` | `PAKAILINK_ENDPOINT_TOPUP_PAYMENT` |
| **Top-up** | `topup.inquiry_status` | `/api/v1.0/customer-topup/status` | `PAKAILINK_ENDPOINT_TOPUP_STATUS` |
| **Product** | `product.list` | `/api/v1.0/product/list` | `PAKAILINK_ENDPOINT_PRODUCT_LIST` |

## Migration Guide

### From Hardcoded Endpoints to Config

**Before:**
```php
$response = $this->client->post('/api/v1.0/transfer-va/create-va', $data);
```

**After:**
```php
$response = $this->client->post(
    config('pakailink.endpoints.virtual_account.create'),
    $data
);
```

### Updating Multiple Endpoints

If PakaiLink releases API v2.0, update all at once:

```bash
# Create .env.pakailink-v2
PAKAILINK_BASE_URL=https://api-v2.pakaidonk.id
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/va/create
PAKAILINK_ENDPOINT_VA_INQUIRY=/api/v2.0/va/status
PAKAILINK_ENDPOINT_QRIS_GENERATE=/api/v2.0/qris/generate
# ... etc
```

Or update the config file directly and deploy.

## Best Practices

### 1. Keep Defaults in Config File

Always maintain working defaults in `config/endpoints.php`:
```php
'virtual_account' => [
    'create' => env('PAKAILINK_ENDPOINT_VA_CREATE', '/api/v1.0/transfer-va/create-va'),
    //                                              ↑ Default fallback value
]
```

### 2. Document Endpoint Changes

When updating endpoints, document why:
```php
'virtual_account' => [
    // Updated from v1.0 to v1.1 on 2025-12-01 for new features
    'create' => env('PAKAILINK_ENDPOINT_VA_CREATE', '/api/v1.1/transfer-va/create-va'),
]
```

### 3. Test After Updates

```bash
# After updating endpoints, run integration tests
php artisan test tests/Integration/PakaiLink/

# Or use the test runner
./run-pakailink-tests.sh
```

### 4. Version Control

Commit `config/endpoints.php` to version control:
```bash
git add packages/pgpay/pakailink-sdk/config/endpoints.php
git commit -m "Update PakaiLink endpoints to v2.0"
```

### 5. Environment-Specific Overrides

Use `.env` for environment-specific changes:
```env
# Production uses stable v1.0
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.0/transfer-va/create-va

# Staging tests new v2.0
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/virtual-account/create
```

## Troubleshooting

### Endpoint Returns 404

**Check current endpoint:**
```bash
php artisan tinker
>>> config('pakailink.endpoints.virtual_account.create')
=> "/api/v1.0/transfer-va/create-va"
```

**Test endpoint manually:**
```bash
curl -X POST https://rising-dev.pakailink.id/api/v1.0/transfer-va/create-va \
  -H "Authorization: Bearer $(php artisan tinker --execute='echo app(Pgpay\PakaiLink\Services\PakaiLinkAuthService::class)->getB2BAccessToken();')"
```

**Update if needed:**
```env
# .env
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.1/transfer-va/create
```

### Endpoint Change Not Applied

**Clear config cache:**
```bash
php artisan config:clear
php artisan optimize:clear
```

**Verify change:**
```bash
php artisan tinker
>>> config('pakailink.endpoints.virtual_account.create')
```

### Wrong API Version

If PakaiLink specifies a different version:

```env
# Update all endpoints to v1.1
PAKAILINK_ENDPOINT_VA_CREATE=/api/v1.1/transfer-va/create-va
PAKAILINK_ENDPOINT_VA_INQUIRY=/api/v1.1/transfer-va/status
# ... etc
```

## API Version History

### v1.0 (Current)
- Initial SNAP API implementation
- All payment methods supported
- Standard endpoints

### v1.1 (If released)
- TBD by PakaiLink

### v2.0 (If released)
- TBD by PakaiLink

## Quick Reference

### Update Single Endpoint
```env
PAKAILINK_ENDPOINT_VA_CREATE=/new/path
```

### Update All VA Endpoints
```env
PAKAILINK_ENDPOINT_VA_CREATE=/api/v2.0/va/create
PAKAILINK_ENDPOINT_VA_INQUIRY=/api/v2.0/va/status
PAKAILINK_ENDPOINT_VA_UPDATE=/api/v2.0/va/update
PAKAILINK_ENDPOINT_VA_DELETE=/api/v2.0/va/delete
```

### Rollback to Defaults
Remove environment variables from `.env`, config will use defaults.

## Support

### Check PakaiLink Documentation
- https://pakaidonk.id/dokumentasi-api/

### Test Endpoints
```bash
# List all configured endpoints
php artisan tinker
>>> config('pakailink.endpoints')

# Test specific endpoint
curl -X POST BASE_URL + ENDPOINT
```

### Contact PakaiLink
For sandbox endpoint changes, contact PakaiLink support.

---

**Last Updated:** 2025-11-27
**Compatible with:** PakaiLink SNAP API v1.0
**SDK Version:** 1.0.0
