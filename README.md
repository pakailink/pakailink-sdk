# PakaiLink SDK for Laravel

A comprehensive Laravel package for integrating with PakaiLink Payment Gateway. This SDK provides SNAP-compliant payment processing for Indonesian payment methods including Virtual Accounts, QRIS, E-Wallets, and Bank Transfers.

## Features

- ✅ **SNAP Compliant** - Follows Standard National API Payment specification
- ✅ **Multiple Payment Methods** - VA, QRIS, E-Wallet, Retail, Bank Transfer
- ✅ **Dual Signature** - RSA-SHA256 (asymmetric) and HMAC-SHA512 (symmetric)
- ✅ **Token Management** - Automatic B2B token generation with caching
- ✅ **Retry Logic** - Automatic retry for network failures
- ✅ **Comprehensive Logging** - Detailed logging for debugging
- ✅ **Type Safe** - Full PHP 8.4 type hints and enums
- ✅ **Exception Handling** - Custom exceptions for different error types

## Requirements

- PHP 8.4+
- Laravel 12.0+
- ext-openssl (for RSA signatures)
- ext-json

## Installation

### Step 1: Add Repository to composer.json

Add the local repository to your main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/pakailink/pakailink-sdk"
        }
    ]
}
```

### Step 2: Install Package

```bash
composer require pakailink/pakailink-sdk
```

### Step 3: Publish Configuration

```bash
php artisan vendor:publish --tag=pakailink-config
```

### Step 4: Configure Environment

Add to your `.env` file:

```env
PAKAILINK_ENV=sandbox
PAKAILINK_BASE_URL=https://rising-dev.pakailink.id
PAKAILINK_CLIENT_ID=your_client_id
PAKAILINK_CLIENT_SECRET=your_client_secret
PAKAILINK_PARTNER_ID=your_partner_id
PAKAILINK_MERCHANT_ID=your_merchant_id
PAKAILINK_CHANNEL_ID=your_channel_id
PAKAILINK_PRIVATE_KEY_PATH=storage/keys/pakailink_private.pem
PAKAILINK_PUBLIC_KEY_PATH=storage/keys/pakailink_public.pem
```

### Step 5: Generate RSA Keys

```bash
# Generate private key
openssl genrsa -out storage/keys/pakailink_private.pem 2048

# Generate public key
openssl rsa -in storage/keys/pakailink_private.pem -pubout -out storage/keys/pakailink_public.pem

# Set permissions
chmod 400 storage/keys/pakailink_private.pem
chmod 444 storage/keys/pakailink_public.pem
```

## Usage

### Basic Service Access

```php
use PakaiLink\Services\PakaiLinkService;

// Get balance
$service = app(PakaiLinkService::class);
$balance = $service->getBalance();

// Get balance history
$history = $service->getBalanceHistory(
    startDate: '2025-01-01',
    endDate: '2025-01-31',
    page: 1,
    limit: 100
);
```

### Virtual Account Service

```php
use PakaiLink\Services\PakaiLinkVirtualAccountService;
use PakaiLink\Data\CreateVirtualAccountData;

$service = app(PakaiLinkVirtualAccountService::class);

// Create Virtual Account
$data = CreateVirtualAccountData::from([
    'amount' => 100000,
    'customer_name' => 'John Doe',
    'bank_code' => '002', // BRI
]);

$response = $service->create($data);
// Returns: ['virtualAccountData' => ['virtualAccountNo' => '8808...', ...]]

// Inquiry VA Status
$status = $service->inquiryStatus($vaNumber, $inquiryRequestId);

// Update VA
$service->update($vaNumber, $trxId, 'U', 150000);

// Delete VA
$service->delete($vaNumber, $trxId);
```

### QRIS Service

```php
use PakaiLink\Services\PakaiLinkQrisService;
use PakaiLink\Data\GenerateQrisData;

$service = app(PakaiLinkQrisService::class);

// Generate QRIS QR Code
$data = GenerateQrisData::from([
    'amount' => 50000,
]);

$response = $service->generateQris($data);
// Returns: ['qrContent' => '...', 'nmid' => '...', ...]

// Inquiry QRIS Status
$status = $service->inquiryStatus($partnerReferenceNo);

// Refund QRIS Transaction
$refund = $service->refund($partnerReferenceNo, 50000, 'Customer request');
```

### Bank Transfer Service

```php
use PakaiLink\Services\PakaiLinkTransferService;
use PakaiLink\Data\TransferToBankData;

$service = app(PakaiLinkTransferService::class);

// Inquiry bank account (verify before transfer)
$data = TransferToBankData::from([
    'beneficiary_bank_code' => '008', // Mandiri
    'beneficiary_account_no' => '1234567890',
    'beneficiary_account_name' => 'Jane Smith',
    'amount' => 100000,
]);

$inquiry = $service->inquiryTransfer($data);

// Execute transfer
$transfer = $service->transferToBank($data);

// Check transfer status
$status = $service->inquiryStatus($partnerReferenceNo);

// Transfer to VA
$vaTransfer = $service->transferToVA(
    beneficiaryVirtualAccountNo: '8808123456789012',
    beneficiaryBankCode: '002',
    amount: 100000
);
```

### E-money/E-wallet Service

```php
use PakaiLink\Services\PakaiLinkEmoneyService;
use PakaiLink\Data\CreateEmoneyPaymentData;

$service = app(PakaiLinkEmoneyService::class);

// Create E-wallet Payment
$data = CreateEmoneyPaymentData::from([
    'channel_id' => 'PAYGOPAY', // GoPay
    'amount' => 75000,
    'customer_name' => 'Bob Wilson',
    'customer_phone' => '081234567890',
]);

$response = $service->createPayment($data);
// Returns: ['webRedirectUrl' => '...', 'referenceNo' => '...']

// Inquiry E-money Status
$status = $service->inquiryStatus($partnerReferenceNo);

// Refund E-money Transaction
$refund = $service->refund($partnerReferenceNo, 75000);
```

### HTTP Client

```php
use PakaiLink\Services\PakaiLinkHttpClient;

$client = app(PakaiLinkHttpClient::class);

// Make authenticated POST request
$response = $client->post('/api/v1.0/endpoint', [
    'key' => 'value',
]);

// Make GET request
$response = $client->get('/api/v1.0/endpoint', [
    'param' => 'value',
]);
```

### Signature Service

```php
use PakaiLink\Services\PakaiLinkSignatureService;

$service = app(PakaiLinkSignatureService::class);

// Generate asymmetric signature (for B2B token)
$signature = $service->generateAsymmetricSignature(
    clientId: 'your_client_id',
    timestamp: now()->toIso8601String()
);

// Generate symmetric signature (for API requests)
$signature = $service->generateSymmetricSignature(
    httpMethod: 'POST',
    endpointUrl: '/api/v1.0/endpoint',
    accessToken: 'your_token',
    requestBody: '{"key":"value"}',
    timestamp: now()->toIso8601String()
);

// Validate callback signature
$isValid = $service->validateCallbackSignature(
    receivedSignature: $request->header('X-SIGNATURE'),
    requestBody: $request->getContent(),
    timestamp: $request->header('X-TIMESTAMP')
);
```

### Authentication Service

```php
use PakaiLink\Services\PakaiLinkAuthService;

$service = app(PakaiLinkAuthService::class);

// Get B2B access token (cached)
$token = $service->getB2BAccessToken();

// Refresh token
$newToken = $service->refreshToken();

// Check if token is expired
if ($service->isTokenExpired()) {
    // Token needs refresh
}

// Clear cached token
$service->clearToken();
```

## Payment Methods Supported

| Method | Code | Description |
|--------|------|-------------|
| Virtual Account | `002`, `008`, `009`, etc. | 13 banks supported |
| QRIS | `QRIS` | Dynamic & Static QR payments |
| E-Wallet | `PAYGOPAY`, `PAYOVO`, etc. | 5 providers (GoPay, OVO, DANA, ShopeePay, LinkAja) |
| Bank Transfer | `002`-`950` | 117 banks supported |
| Retail Payment | `ALFAMART`, `INDOMARET` | 2 stores |

## Enums

### Transaction Types

```php
use PakaiLink\Enums\PakaiLinkTransactionType;

PakaiLinkTransactionType::VIRTUAL_ACCOUNT
PakaiLinkTransactionType::QRIS
PakaiLinkTransactionType::EWALLET
PakaiLinkTransactionType::RETAIL
PakaiLinkTransactionType::TRANSFER_BANK
PakaiLinkTransactionType::TRANSFER_VA
PakaiLinkTransactionType::TOP_UP
PakaiLinkTransactionType::BALANCE_INQUIRY
```

### Transaction Status

```php
use PakaiLink\Enums\PakaiLinkTransactionStatus;

PakaiLinkTransactionStatus::PENDING
PakaiLinkTransactionStatus::PROCESSING
PakaiLinkTransactionStatus::SUCCESS
PakaiLinkTransactionStatus::FAILED
PakaiLinkTransactionStatus::EXPIRED
PakaiLinkTransactionStatus::CANCELLED
```

### Bank Codes

```php
use PakaiLink\Enums\PakaiLinkBankCode;

PakaiLinkBankCode::BRI->value         // '002'
PakaiLinkBankCode::MANDIRI->value     // '008'
PakaiLinkBankCode::BNI->value         // '009'
PakaiLinkBankCode::BCA->value         // '014'

// Get label
PakaiLinkBankCode::BRI->getLabel()    // 'Bank BRI'

// Check type
PakaiLinkBankCode::BSI->isSyariah()   // true
PakaiLinkBankCode::JAGO->isDigital()  // true
```

## Exceptions

All exceptions extend `PakaiLink\Exceptions\PakaiLinkException`:

```php
use PakaiLink\Exceptions\PakaiLinkException;
use PakaiLink\Exceptions\PakaiLinkAuthenticationException;
use PakaiLink\Exceptions\PakaiLinkSignatureException;
use PakaiLink\Exceptions\PakaiLinkValidationException;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

try {
    $service->getBalance();
} catch (PakaiLinkAuthenticationException $e) {
    // Handle auth errors
} catch (PakaiLinkException $e) {
    // Handle other PakaiLink errors
    $context = $e->getContext();
}
```

## Configuration

The package uses `config/pakailink.php` for configuration. Key settings:

```php
return [
    'env' => env('PAKAILINK_ENV', 'sandbox'),
    'base_url' => env('PAKAILINK_BASE_URL'),

    'credentials' => [
        'client_id' => env('PAKAILINK_CLIENT_ID'),
        'client_secret' => env('PAKAILINK_CLIENT_SECRET'),
        'partner_id' => env('PAKAILINK_PARTNER_ID'),
        'merchant_id' => env('PAKAILINK_MERCHANT_ID'),
        'channel_id' => env('PAKAILINK_CHANNEL_ID'),
    ],

    'keys' => [
        'private_key_path' => base_path(env('PAKAILINK_PRIVATE_KEY_PATH')),
        'public_key_path' => base_path(env('PAKAILINK_PUBLIC_KEY_PATH')),
    ],

    'timeout' => env('PAKAILINK_TIMEOUT', 30),
    'retry_times' => env('PAKAILINK_RETRY_TIMES', 3),
    'retry_delay' => env('PAKAILINK_RETRY_DELAY', 1000),

    'cache' => [
        'token_ttl' => 840, // 14 minutes
        'token_key' => 'pakailink:access_token',
    ],
];
```

## Logging

The package logs all activities to the `pakailink` channel:

```php
// config/logging.php
'pakailink' => [
    'driver' => 'daily',
    'path' => storage_path('logs/payments/pakailink.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 90,
],
```

## Architecture

```
PakaiLink\Services\PakaiLinkService (Main)
├── PakaiLinkHttpClient (Authenticated requests)
│   ├── PakaiLinkAuthService (Token management)
│   │   └── PakaiLinkSignatureService (Asymmetric RSA)
│   └── PakaiLinkSignatureService (Symmetric HMAC)
└── Balance Operations
```

## Security

### RSA Keys

- Private key used for B2B token request signatures (RSA-SHA256)
- Public key uploaded to PakaiLink dashboard
- Keys stored in `storage/keys/` with restrictive permissions
- Keys excluded from version control

### Signatures

- **Asymmetric (RSA-SHA256):** For B2B access token requests
- **Symmetric (HMAC-SHA512):** For all other API requests
- **Callback Validation:** HMAC-SHA512 with timing-safe comparison

### Token Caching

- Access tokens cached for 14 minutes (expire in 15 minutes)
- Automatic refresh on expiry
- Cache key: `pakailink:access_token`

## Testing

```bash
# Test service resolution
php artisan tinker
>>> $service = app(\PakaiLink\Services\PakaiLinkService::class);
>>> $balance = $service->getBalance();

# Test signature generation
>>> $sig = app(\PakaiLink\Services\PakaiLinkSignatureService::class);
>>> $signature = $sig->generateSymmetricSignature(...);
```

## Error Handling

```php
use PakaiLink\Exceptions\PakaiLinkException;
use Illuminate\Support\Facades\Log;

try {
    $result = $client->post('/api/v1.0/endpoint', $data);
} catch (PakaiLinkException $e) {
    Log::error('PakaiLink error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'context' => $e->getContext(),
    ]);

    // Handle error
}
```

## Package Structure

```
packages/pakailink/pakailink-sdk/
├── src/
│   ├── Services/
│   │   ├── PakaiLinkSignatureService.php
│   │   ├── PakaiLinkAuthService.php
│   │   ├── PakaiLinkHttpClient.php
│   │   └── PakaiLinkService.php
│   ├── Exceptions/
│   │   ├── PakaiLinkException.php
│   │   ├── PakaiLinkAuthenticationException.php
│   │   ├── PakaiLinkSignatureException.php
│   │   ├── PakaiLinkValidationException.php
│   │   └── PakaiLinkTransactionException.php
│   ├── Enums/
│   │   ├── PakaiLinkTransactionType.php
│   │   ├── PakaiLinkTransactionStatus.php
│   │   └── PakaiLinkBankCode.php
│   ├── Contracts/
│   ├── Facades/
│   └── PakaiLinkServiceProvider.php
├── config/
│   └── pakailink.php
├── composer.json
└── README.md
```

## Extending the Package

### Add Payment Method Service

Create a new service in your application:

```php
namespace App\Services\PakaiLink;

use PakaiLink\Services\PakaiLinkHttpClient;

class CustomPaymentService
{
    public function __construct(
        protected PakaiLinkHttpClient $client
    ) {}

    public function processPayment(array $data): array
    {
        return $this->client->post('/api/v1.0/custom-endpoint', $data);
    }
}
```

### Register Custom Service

In your application's service provider:

```php
$this->app->singleton(CustomPaymentService::class, function ($app) {
    return new CustomPaymentService(
        client: $app->make(\PakaiLink\Services\PakaiLinkHttpClient::class)
    );
});
```

## Support

- **PakaiLink Documentation:** https://pakaidonk.id/en/dokumentasi-api/
- **Issues:** Report issues in your project repository
- **Questions:** Contact your development team

## License

MIT License

## Credits

Developed by BigPG Team for Laravel applications integrating with PakaiLink Payment Gateway.

---

**Version:** 1.0.0

**Status:** Production Ready

**Last Updated:** 2025-11-18
