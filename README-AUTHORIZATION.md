# PakaiLink SDK - Authorization Guide

## Quick Start

### 1. Configuration

Set these in your `.env`:

```env
PAKAILINK_CLIENT_ID=your_client_id
PAKAILINK_CLIENT_SECRET=your_client_secret
PAKAILINK_PARTNER_ID=your_partner_id

# Path to your RSA private key
PAKAILINK_PRIVATE_KEY_PATH=storage/keys/pakailink_private.pem
```

### 2. Get Access Token

```php
use Pgpay\PakaiLink\Services\PakaiLinkAuthService;

$authService = app(PakaiLinkAuthService::class);

// Get token (automatically cached for 14 minutes)
$token = $authService->getB2BAccessToken();
```

### 3. Use in Your Code

The SDK automatically handles authentication. Just use the services:

```php
// Virtual Account - auth handled automatically
$vaService = app(PakaiLinkVirtualAccountService::class);
$va = $vaService->create($data);

// QRIS - auth handled automatically
$qrisService = app(PakaiLinkQrisService::class);
$qris = $qrisService->generateQris($data);
```

## Manual Token Management

### Check Token Status
```php
$authService->isTokenExpired(); // true/false
$authService->getTokenInfo();   // Array with token details
```

### Refresh Token
```php
$authService->refreshToken(); // Clears cache and gets new token
```

### Clear Token
```php
$authService->clearToken(); // Remove from cache
```

## How It Works

1. **First Request**: SDK requests B2B token using RSA signature
2. **Token Cached**: Token stored for 14 minutes (expires in 15)
3. **Subsequent Requests**: SDK reuses cached token
4. **Auto-Refresh**: If token expired, SDK automatically refreshes

## Troubleshooting

### "40100 - Unauthorized" Error

**Causes:**
- Invalid `CLIENT_ID` or `CLIENT_SECRET`
- Wrong RSA private key
- Rate limiting (sandbox has limits)

**Solutions:**
```bash
# 1. Verify credentials
php artisan tinker
config('pakailink.credentials.client_id')
config('pakailink.credentials.client_secret')

# 2. Test authentication
$auth = app(\Pgpay\PakaiLink\Services\PakaiLinkAuthService::class);
$auth->clearToken();
$token = $auth->getB2BAccessToken();

# 3. Check RSA key
ls -la storage/keys/pakailink_private.pem
openssl rsa -in storage/keys/pakailink_private.pem -check
```

### Rate Limiting in Sandbox

If you hit rate limits in sandbox:
- Wait 1-2 hours before retrying
- Use file cache (not array cache) to reduce auth requests
- Minimize token refresh calls

## Testing

### Disable Rate Limit Issues in Tests

In `phpunit.xml`, use file cache instead of array:

```xml
<env name="CACHE_STORE" value="file"/>
```

This allows token reuse across test classes, reducing auth requests from ~10+ to just 1-2.

---

**That's it!** The SDK handles all authorization automatically. Just configure credentials and use the services.
