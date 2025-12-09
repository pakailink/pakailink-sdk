# PakaiLink SDK - QRIS Payment Guide

## Quick Start

### 1. Generate QRIS Code

```php
use Pgpay\PakaiLink\Services\PakaiLinkQrisService;
use Pgpay\PakaiLink\Data\GenerateQrisData;

$qrisService = app(PakaiLinkQrisService::class);

$qris = $qrisService->generateQris(GenerateQrisData::from([
    'amount' => 50000,
]));

// Get QR content to display
$qrContent = $qris['qrContent']; // QR string to encode as QR image
$referenceNo = $qris['referenceNo']; // PakaiLink reference
$partnerRefNo = $qris['partnerReferenceNo']; // Your reference (store this)
```

### 2. Display QR Code to Customer

```php
// Option 1: Using a QR code library
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrImage = QrCode::size(300)->generate($qrContent);

// Option 2: Return to frontend
return response()->json([
    'qr_content' => $qrContent,
    'reference_no' => $partnerRefNo,
    'expires_at' => $qris['validityPeriod'],
]);
```

### 3. Check Payment Status

```php
// Use the partner reference number you stored
$status = $qrisService->inquiryStatus($partnerRefNo);

if ($status['latestTransactionStatus'] === '00') {
    // Payment successful!
    $paidAmount = $status['additionalInfo']['nominalPaid']['value'];
    $serviceFee = $status['additionalInfo']['serviceFee']['value'];
    $received = $status['additionalInfo']['totalReceive']['value'];
}
```

### 4. Handle Callback (Automatic)

```php
// Listen for payment event
use Pgpay\PakaiLink\Events\QrisPaymentReceived;

Event::listen(function (QrisPaymentReceived $event) {
    $data = $event->data;

    // Get payment details
    $partnerRefNo = $data->originalPartnerReferenceNo;
    $amount = $data->getAmount();
    $customer = $data->additionalInfo['customerData'] ?? 'N/A';

    // Update your order/transaction
    $order = Order::where('pakailink_ref', $partnerRefNo)->first();
    $order->markAsPaid($amount);
});
```

## Advanced Usage

### Custom Validity Period

```php
$qris = $qrisService->generateQris(GenerateQrisData::from([
    'amount' => 100000,
    'validity_period' => now()->addHours(2), // Custom expiry
]));
```

### Custom Store and Terminal

```php
$qris = $qrisService->generateQris(GenerateQrisData::from([
    'amount' => 75000,
    'store_id' => 'STORE001',
    'terminal_id' => 'TERM001',
]));
```

### With Partner Reference

```php
$myRefNo = 'ORDER-'.time();

$qris = $qrisService->generateQris(GenerateQrisData::from([
    'amount' => 50000,
    'partner_reference_no' => $myRefNo,
]));
```

## Response Structure

### Generate Response
```json
{
  "responseCode": "2004700",
  "responseMessage": "Successful",
  "referenceNo": "QRA0000038",
  "partnerReferenceNo": "...",
  "qrContent": "00020101021226640017ID.CO...",
  "merchantName": "Your Store",
  "storeId": "PAKAILINK",
  "terminalId": "ID1024361878720",
  "validityPeriod": "2025-03-06T23:59:59+07:00",
  "amount": {
    "currency": "IDR",
    "value": "50000.00"
  },
  "feeAmount": {
    "currency": "IDR",
    "value": "3000.00"
  }
}
```

### Inquiry Response
```json
{
  "responseCode": "2005300",
  "responseMessage": "Successful",
  "originalPartnerReferenceNo": "...",
  "latestTransactionStatus": "00",
  "transactionStatusDesc": "Success",
  "amount": {
    "value": "50000.00",
    "currency": "IDR"
  },
  "additionalInfo": {
    "customerData": "Customer Name",
    "rrn": "123132131",
    "payor": "OVO/DANA/etc",
    "serviceFee": {
      "value": "3000.00",
      "currency": "IDR"
    },
    "totalReceive": {
      "value": "47000.00",
      "currency": "IDR"
    }
  }
}
```

## Transaction Status Codes

| Code | Status | Description |
|------|--------|-------------|
| `00` | Success | Payment completed |
| `01` | Started | QR generated, not paid yet |
| `02` | Paid | Payment processing |
| `99` | Canceled | Payment canceled |

## Complete Example

```php
use Pgpay\PakaiLink\Services\PakaiLinkQrisService;
use Pgpay\PakaiLink\Data\GenerateQrisData;
use Pgpay\PakaiLink\Events\QrisPaymentReceived;

class CheckoutController extends Controller
{
    public function createQrisPayment(Request $request)
    {
        $qrisService = app(PakaiLinkQrisService::class);

        // 1. Generate QRIS
        $qris = $qrisService->generateQris(GenerateQrisData::from([
            'amount' => $request->amount,
            'store_id' => 'WEBSTORE',
            'terminal_id' => 'WEB001',
        ]));

        // 2. Store reference
        $order = Order::create([
            'amount' => $request->amount,
            'pakailink_ref' => $qris['partnerReferenceNo'],
            'qr_content' => $qris['qrContent'],
            'status' => 'pending',
        ]);

        // 3. Return QR to frontend
        return response()->json([
            'qr_content' => $qris['qrContent'],
            'order_id' => $order->id,
            'expires_at' => $qris['validityPeriod'],
        ]);
    }

    public function checkStatus($orderId)
    {
        $order = Order::findOrFail($orderId);

        $qrisService = app(PakaiLinkQrisService::class);
        $status = $qrisService->inquiryStatus($order->pakailink_ref);

        return response()->json([
            'status' => $status['latestTransactionStatus'],
            'paid' => $status['latestTransactionStatus'] === '00',
        ]);
    }
}

// Event Listener
Event::listen(function (QrisPaymentReceived $event) {
    $partnerRef = $event->data->originalPartnerReferenceNo;

    $order = Order::where('pakailink_ref', $partnerRef)->first();
    if ($order && $event->data->latestTransactionStatus === '00') {
        $order->update(['status' => 'paid']);

        // Send confirmation email, process order, etc.
    }
});
```

## Transaction Limits

- **Minimum Amount:** IDR 10,000
- **Maximum Amount:** No limit (varies by customer's e-wallet)
- **Validity:** Default 1 hour (customizable)
- **Settlement:** Real-time to PakaiLink balance

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2004700` | Successful | Success |
| `4004702` | Invalid Mandatory Field | Check all required fields |
| `4014700` | Unauthorized | Check credentials |
| `4094700` | Conflict | Duplicate external ID (retry) |
| `5004702` | Backend failure | Retry request |

## Testing

```bash
# Test QRIS generation
php artisan test --filter=qris

# Or use tinker
php artisan tinker
$qris = app(\Pgpay\PakaiLink\Services\PakaiLinkQrisService::class);
$result = $qris->generateQris(\Pgpay\PakaiLink\Data\GenerateQrisData::from(['amount' => 50000]));
```

---

**That's it!** Generate QR, display to customer, handle callback. Done.
