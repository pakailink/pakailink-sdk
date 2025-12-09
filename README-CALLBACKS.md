# PakaiLink SDK - Callback Handling

This document explains how to use the PakaiLink SDK's built-in callback handling system.

## Overview

The PakaiLink SDK provides automatic callback handling for all payment methods. When you install the package, callback routes are automatically registered and ready to receive webhooks from PakaiLink.

## Features

✅ Automatic callback route registration
✅ Signature validation middleware
✅ Event-driven architecture
✅ Support for all payment methods (VA, QRIS, E-money, Transfer)
✅ Comprehensive error handling
✅ Automatic logging

## Callback URLs

After installing the package, the following callback endpoints are automatically available:

| Payment Method | URL | Event Dispatched |
|---|---|---|
| Virtual Account | `POST /api/pakailink/callbacks/virtual-account` | `VirtualAccountPaid` |
| QRIS | `POST /api/pakailink/callbacks/qris` | `QrisPaymentReceived` |
| E-money | `POST /api/pakailink/callbacks/emoney` | `EmoneyPaymentReceived` |
| Transfer | `POST /api/pakailink/callbacks/transfer` | `TransferCompleted` |

## Configuration

### 1. Configure Callback Base URL

Add to your `.env`:

```env
PAKAILINK_CALLBACK_BASE_URL="${APP_URL}/api/pakailink/callbacks"
PAKAILINK_CALLBACKS_ENABLED=true
PAKAILINK_CALLBACKS_PREFIX="api/pakailink/callbacks"
```

### 2. Register Callback URLs in PakaiLink Dashboard

For each payment method, register the following URLs in your PakaiLink merchant dashboard:

- **Virtual Account**: `https://yourdomain.com/api/pakailink/callbacks/virtual-account`
- **QRIS**: `https://yourdomain.com/api/pakailink/callbacks/qris`
- **E-money**: `https://yourdomain.com/api/pakailink/callbacks/emoney`
- **Transfer**: `https://yourdomain.com/api/pakailink/callbacks/transfer`

## Handling Callbacks in Your Application

The SDK dispatches Laravel events when callbacks are received. Listen to these events in your application:

### 1. Create Event Listeners

```bash
php artisan make:listener HandleVirtualAccountPayment
```

### 2. Register Listeners

In `app/Providers/EventServiceProvider.php`:

```php
use Pgpay\PakaiLink\Events\VirtualAccountPaid;
use Pgpay\PakaiLink\Events\QrisPaymentReceived;
use Pgpay\PakaiLink\Events\EmoneyPaymentReceived;
use Pgpay\PakaiLink\Events\TransferCompleted;

protected $listen = [
    VirtualAccountPaid::class => [
        HandleVirtualAccountPayment::class,
    ],
    QrisPaymentReceived::class => [
        HandleQrisPayment::class,
    ],
    EmoneyPaymentReceived::class => [
        HandleEmoneyPayment::class,
    ],
    TransferCompleted::class => [
        HandleTransferCompleted::class,
    ],
];
```

### 3. Implement Listener Logic

#### Virtual Account Payment Example

```php
<?php

namespace App\Listeners;

use Pgpay\PakaiLink\Events\VirtualAccountPaid;
use Illuminate\Support\Facades\Log;

class HandleVirtualAccountPayment
{
    public function handle(VirtualAccountPaid $event): void
    {
        $partnerReferenceNo = $event->getPartnerReferenceNo();
        $amount = $event->getAmount();
        $vaNumber = $event->getVirtualAccountNo();
        $bankCode = $event->getBankCode();

        // Find your transaction
        $transaction = \App\Models\PakaiLinkVirtualAccount::where(
            'partner_reference_no',
            $partnerReferenceNo
        )->first();

        if (!$transaction) {
            Log::error('Transaction not found', ['ref' => $partnerReferenceNo]);
            return;
        }

        // Update transaction status
        $transaction->update([
            'status' => $event->isSuccess() ? 'completed' : 'failed',
            'paid_at' => now(),
        ]);

        // Update your business logic here
        // e.g., Update user balance, send notification, etc.
    }
}
```

#### QRIS Payment Example

```php
<?php

namespace App\Listeners;

use Pgpay\PakaiLink\Events\QrisPaymentReceived;

class HandleQrisPayment
{
    public function handle(QrisPaymentReceived $event): void
    {
        $partnerReferenceNo = $event->getPartnerReferenceNo();
        $amount = $event->getAmount();

        // Find your transaction
        $transaction = \App\Models\PakaiLinkQrisPayment::where(
            'partner_reference_no',
            $partnerReferenceNo
        )->first();

        if ($event->isSuccess()) {
            $transaction->update(['status' => 'completed']);
            // Update your business logic
        }
    }
}
```

## Available Event Methods

### VirtualAccountPaid Event

```php
$event->getPartnerReferenceNo();  // string - Your reference number
$event->getAmount();              // float - Payment amount
$event->getVirtualAccountNo();    // string - VA number that was paid
$event->getBankCode();            // string|null - Bank code
$event->isSuccess();              // bool - Payment status
$event->data;                     // VirtualAccountCallbackData - Full data object
$event->rawPayload;               // array - Raw payload from PakaiLink
```

### QrisPaymentReceived Event

```php
$event->getPartnerReferenceNo();  // string - Your reference number
$event->getAmount();              // float - Payment amount
$event->getReferenceNo();         // string - PakaiLink reference number
$event->isSuccess();              // bool - Payment status
$event->data;                     // QrisCallbackData - Full data object
$event->rawPayload;               // array - Raw payload from PakaiLink
```

### EmoneyPaymentReceived Event

```php
$event->getPartnerReferenceNo();  // string - Your reference number
$event->getAmount();              // float - Payment amount
$event->getChannelId();           // string - Channel ID (GOPAY, OVO, etc.)
$event->getChannelName();         // string - Channel name (GoPay, OVO, etc.)
$event->isSuccess();              // bool - Payment status
$event->data;                     // EmoneyCallbackData - Full data object
$event->rawPayload;               // array - Raw payload from PakaiLink
```

### TransferCompleted Event

```php
$event->getPartnerReferenceNo();    // string - Your reference number
$event->getAmount();                // float - Transfer amount
$event->getBeneficiaryBankCode();   // string - Beneficiary bank code
$event->getBeneficiaryAccountNo();  // string - Beneficiary account number
$event->isSuccess();                // bool - Transfer status
$event->data;                       // TransferCallbackData - Full data object
$event->rawPayload;                 // array - Raw payload from PakaiLink
```

## Security

### Signature Validation

All callbacks are automatically validated using the `ValidatePakaiLinkSignature` middleware. Invalid signatures are rejected with a 401 response.

The middleware checks:
- `X-SIGNATURE` header is present
- `X-TIMESTAMP` header is present
- Signature matches the calculated signature

### Callback Headers

PakaiLink sends the following headers with each callback:

- `X-SIGNATURE`: HMAC-SHA512 signature
- `X-TIMESTAMP`: ISO 8601 timestamp
- `Content-Type`: application/json

## Error Handling

### Callback Failures

If callback processing fails, the SDK returns appropriate error responses:

| Error | Response Code | HTTP Status |
|---|---|---|
| Invalid signature | 4010001 | 401 |
| Missing signature/timestamp | 4010000 | 401 |
| Validation exception | 4000000 | 400 |
| Internal error | 5000000 | 500 |

### Logging

All callbacks are automatically logged to the `pakailink` log channel:

```
storage/logs/payments/pakailink.log
```

Log entries include:
- Callback type
- Signature validation result
- Partner reference number
- Payment amount
- Transaction status

## Testing Callbacks

### Manual Testing with cURL

```bash
# Virtual Account Callback
curl -X POST https://yourdomain.com/api/pakailink/callbacks/virtual-account \
  -H "Content-Type: application/json" \
  -H "X-SIGNATURE: your_signature" \
  -H "X-TIMESTAMP: 2025-11-27T10:00:00+07:00" \
  -d '{
    "partnerServiceId": "12345",
    "customerNo": "123456",
    "virtualAccountNo": "888801234567890",
    "virtualAccountName": "John Doe",
    "partnerReferenceNo": "VA-20251127-12345678",
    "amount": {
      "value": "100000.00",
      "currency": "IDR"
    },
    "latestTransactionStatus": "00",
    "transactionStatusDesc": "Success",
    "trxDateTime": "2025-11-27T10:00:00+07:00"
  }'
```

### Testing with PakaiLink Sandbox

1. Create a test transaction in sandbox
2. Use PakaiLink sandbox tools to trigger callback
3. Check your application logs for received callback

## Disabling Callbacks

To disable automatic callback handling:

```env
PAKAILINK_CALLBACKS_ENABLED=false
```

Or in `config/pakailink.php`:

```php
'callbacks' => [
    'enabled' => false,
    // ...
],
```

## Customizing Callback Routes

You can customize the callback URL prefix:

```env
PAKAILINK_CALLBACKS_PREFIX="webhooks/pakailink"
```

This will change the URLs to:
- `POST /webhooks/pakailink/virtual-account`
- `POST /webhooks/pakailink/qris`
- etc.

## Advanced Usage

### Accessing Raw Payload

```php
use Pgpay\PakaiLink\Events\VirtualAccountPaid;

class HandleVirtualAccountPayment
{
    public function handle(VirtualAccountPaid $event): void
    {
        // Access parsed data
        $data = $event->data;

        // Access raw payload from PakaiLink
        $raw = $event->rawPayload;

        // Use additional info
        $additionalInfo = $data->additionalInfo;
    }
}
```

### Queue Listeners

For better performance, queue your listeners:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class HandleVirtualAccountPayment implements ShouldQueue
{
    public function handle(VirtualAccountPaid $event): void
    {
        // This will be processed in the background
    }
}
```

## Troubleshooting

### Callbacks Not Received

1. Check callback URLs are registered in PakaiLink dashboard
2. Verify `PAKAILINK_CALLBACKS_ENABLED=true`
3. Check firewall allows incoming POST requests
4. Review logs: `storage/logs/payments/pakailink.log`

### Invalid Signature Errors

1. Verify `PAKAILINK_CLIENT_SECRET` is correct
2. Check system time is synchronized
3. Ensure callback private key is correctly configured
4. Review signature validation logs

### Event Not Firing

1. Verify event listeners are registered in `EventServiceProvider`
2. Clear cache: `php artisan event:clear`
3. Check event is being dispatched in logs

## Support

For issues with the PakaiLink SDK:
- Check logs: `storage/logs/payments/pakailink.log`
- Review PakaiLink documentation: https://pakaidonk.id/dokumentasi-api/

For PakaiLink service issues:
- Contact PakaiLink support
- Check PakaiLink dashboard status
