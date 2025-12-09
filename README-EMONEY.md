# PakaiLink SDK - E-money Payment Guide

## Quick Start

### 1. Create E-money Payment

```php
use Pgpay\PakaiLink\Services\PakaiLinkEmoneyService;
use Pgpay\PakaiLink\Data\CreateEmoneyPaymentData;

$emoneyService = app(PakaiLinkEmoneyService::class);

$payment = $emoneyService->createPayment(CreateEmoneyPaymentData::from([
    'amount' => 75000,
    'customer_id' => '31857118',
    'customer_name' => 'John Doe',
    'customer_phone' => '08123456789',
    'product_code' => 'PAYOVO',
    'emoney_phone' => '08123456789', // OVO phone number
]));

// Get payment URL
$paymentUrl = $payment['emoneyData']['additionalInfo']['urlPayment'];
$partnerRefNo = $payment['emoneyData']['partnerReferenceNo']; // Store this!
```

### 2. Redirect Customer to Payment

```php
return redirect($paymentUrl);

// Or for API
return response()->json([
    'payment_url' => $paymentUrl,
    'reference_no' => $partnerRefNo,
]);
```

### 3. Check Payment Status

```php
$status = $emoneyService->inquiryStatus($partnerRefNo);

if ($status['latestTransactionStatus'] === '00') {
    // Payment successful!
    $received = $status['additionalInfo']['totalReceive']['value'];
}
```

### 4. Handle Callback

```php
use Pgpay\PakaiLink\Events\EmoneyPaymentReceived;

Event::listen(function (EmoneyPaymentReceived $event) {
    $data = $event->data;

    // Update order
    Order::where('pakailink_ref', $data->originalPartnerReferenceNo)
        ->update(['status' => 'paid']);
});
```

## Supported E-wallets

| Product Code | E-wallet |
|--------------|----------|
| `PAYOVO` | OVO |
| `PAYDANA` | DANA |
| `PAYGOPAY` | GoPay |
| `PAYLINKAJA` | LinkAja |
| `PAYSHOPEE` | ShopeePay |

## Advanced Options

### With Email and Bill Title

```php
$payment = $emoneyService->createPayment(CreateEmoneyPaymentData::from([
    'amount' => 100000,
    'customer_id' => '31857119',
    'customer_name' => 'Jane Doe',
    'customer_phone' => '08123456789',
    'product_code' => 'PAYDANA',
    'emoney_phone' => '08123456789',
    'customer_email' => 'jane@example.com',
    'bill_title' => 'Payment for Order #12345',
]));
```

### Custom Expiry

```php
$payment = $emoneyService->createPayment(CreateEmoneyPaymentData::from([
    'amount' => 50000,
    'customer_id' => '31857120',
    'customer_name' => 'Bob Smith',
    'customer_phone' => '08123456789',
    'product_code' => 'PAYOVO',
    'emoney_phone' => '08123456789',
    'expired_date' => now()->addHours(2),
]));
```

## Transaction Limits

- **Minimum Amount:** IDR 10,000
- **Maximum Amount:** IDR 2,500,000
- **Validity:** Default 24 hours
- **Settlement:** 1-4 days to PakaiLink balance

## Complete Example

```php
class CheckoutController extends Controller
{
    public function payWithEwallet(Request $request)
    {
        $emoneyService = app(PakaiLinkEmoneyService::class);

        $payment = $emoneyService->createPayment(CreateEmoneyPaymentData::from([
            'amount' => $request->amount,
            'customer_id' => (string) auth()->id(),
            'customer_name' => auth()->user()->name,
            'customer_phone' => auth()->user()->phone,
            'product_code' => 'PAY' . strtoupper($request->wallet), // PAYOVO, PAYDANA
            'emoney_phone' => $request->ewallet_phone,
            'customer_email' => auth()->user()->email,
            'bill_title' => "Order #{$request->order_id}",
        ]));

        // Store payment
        Payment::create([
            'order_id' => $request->order_id,
            'pakailink_ref' => $payment['emoneyData']['partnerReferenceNo'],
            'payment_code' => $payment['emoneyData']['paymentCode'],
            'status' => 'pending',
        ]);

        // Redirect to e-wallet
        return redirect($payment['emoneyData']['additionalInfo']['urlPayment']);
    }
}
```

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2002900` | Successful | Success |
| `4002902` | Invalid Mandatory Field | Add missing fields |
| `4032902` | Exceeds Amount Limit | Check min 10k, max 2.5M |
| `4092901` | Duplicate Reference | Use unique reference |

---

**Flow:** Create payment → Redirect to e-wallet → Customer pays → Callback received → Order complete.
