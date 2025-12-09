# PakaiLink SDK - Retail Payment Guide

## Quick Start

### 1. Create Retail Payment

```php
use Pgpay\PakaiLink\Services\PakaiLinkRetailService;
use Pgpay\PakaiLink\Data\CreateRetailPaymentData;

$retailService = app(PakaiLinkRetailService::class);

$payment = $retailService->createPayment(CreateRetailPaymentData::from([
    'amount' => 50000,
    'customer_id' => '31857119',
    'customer_name' => 'John Doe',
    'product_code' => 'ALFAMART',
    'customer_phone' => '085745512488',
]));

// Get payment code to display
$paymentCode = $payment['paymentData']['paymentCode'];
$partnerRefNo = $payment['paymentData']['partnerReferenceNo']; // Store this!
```

### 2. Display Payment Code

```php
return response()->json([
    'payment_code' => $paymentCode,
    'retail' => 'Alfamart',
    'amount' => 50000,
    'instructions' => 'Show this code at Alfamart cashier',
]);
```

### 3. Check Payment Status

```php
$status = $retailService->inquiryStatus($partnerRefNo);

if ($status['latestTransactionStatus'] === '00') {
    // Payment completed at retail store!
    $received = $status['additionalInfo']['totalReceive']['value'];
}
```

### 4. Handle Callback

```php
use Pgpay\PakaiLink\Events\RetailPaymentReceived;

Event::listen(function (RetailPaymentReceived $event) {
    $data = $event->data;

    if ($data->isSuccessful()) {
        $partnerRefNo = $data->partnerReferenceNo;
        $paidAmount = $data->getPaidAmountValue();
        $credited = $data->getCreditBalanceValue();

        // Update order
        Order::where('pakailink_ref', $partnerRefNo)->update([
            'status' => 'paid',
        ]);
    }
});
```

## Supported Retailers

| Product Code | Retail Store |
|--------------|--------------|
| `ALFAMART` | Alfamart |
| `INDOMARET` | Indomaret |

## Transaction Limits

- **Minimum Amount:** IDR 15,000
- **Maximum Amount:** IDR 2,500,000
- **Validity:** Default 24 hours
- **Settlement:** 2-4 days to PakaiLink balance

## Complete Example

```php
class PaymentController extends Controller
{
    public function createRetailPayment(Request $request)
    {
        $retailService = app(PakaiLinkRetailService::class);

        $payment = $retailService->createPayment(CreateRetailPaymentData::from([
            'amount' => $request->amount,
            'customer_id' => (string) auth()->id(),
            'customer_name' => auth()->user()->name,
            'product_code' => $request->retail, // ALFAMART or INDOMARET
            'customer_phone' => $request->phone,
            'customer_email' => $request->email,
        ]));

        // Store payment
        Payment::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'payment_code' => $payment['paymentData']['paymentCode'],
            'pakailink_ref' => $payment['paymentData']['partnerReferenceNo'],
            'retail' => $request->retail,
            'status' => 'pending',
        ]);

        return response()->json([
            'payment_code' => $payment['paymentData']['paymentCode'],
            'retail' => $request->retail,
            'instructions' => "Pay at {$request->retail} with code: {$payment['paymentData']['paymentCode']}",
        ]);
    }
}
```

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2003100` | Successful | Success |
| `4003102` | Invalid Mandatory Field | Add missing fields |
| `4033102` | Exceeds Amount Limit | Check min 15k, max 2.5M |
| `4093101` | Duplicate Reference | Use unique reference |

---

**Flow:** Create payment → Get code → Customer pays at store → Callback received → Order complete.
