# PakaiLink SDK - Customer Topup Guide

## Quick Start

### 1. Inquiry Customer Account (Required First)

```php
use Pgpay\PakaiLink\Services\PakaiLinkTopupService;
use Pgpay\PakaiLink\Data\TopupPaymentData;

$topupService = app(PakaiLinkTopupService::class);

// First, inquiry to verify customer and get sessionId
$inquiry = $topupService->inquiryCustomer(
    customerNumber: '08113338390',
    productCode: 'OVO',
    amount: 100000
);

$sessionId = $inquiry['sessionId']; // Required for topup!
$customerName = $inquiry['customerName']; // Verify customer
```

### 2. Create Customer Topup

```php
// Use the sessionId from inquiry
$topup = $topupService->createTopup(TopupPaymentData::from([
    'amount' => 100000,
    'customer_number' => '08113338390',
    'product_code' => 'OVO',
    'session_id' => $sessionId, // From inquiry!
]));

$partnerRefNo = $topup['partnerReferenceNo']; // Store this!
```

### 3. Check Initial Status

```php
$initialStatus = $topup['additionalInfo']['transactionStatus'];
// Usually '03' (Pending) - topup processing in background
```

### 4. Check Topup Status

```php
$status = $topupService->inquiryStatus($partnerRefNo);

if ($status['latestTransactionStatus'] === '00') {
    // Topup successful!
    $customerName = $status['customerName'];
}
```

### 5. Handle Callback

```php
use Pgpay\PakaiLink\Events\TopupCompleted;

Event::listen(function (TopupCompleted $event) {
    $data = $event->data;

    if ($data->isSuccessful()) {
        $partnerRefNo = $data->partnerReferenceNo;
        $accountName = $data->accountName;
        $paidAmount = $data->getPaidAmountValue();

        // Update topup record
        Topup::where('pakailink_ref', $partnerRefNo)->update([
            'status' => 'completed',
            'customer_name' => $accountName,
        ]);
    }
});
```

## Supported E-wallets

| Product Code | E-wallet |
|--------------|----------|
| `OVO` | OVO |
| `DANA` | DANA |
| `GOPAY` | GoPay |
| `LINKAJA` | LinkAja |
| `SHOPEEPAY` | ShopeePay |

## Transaction Limits

- **Minimum Amount:** IDR 10,000 (IDR 20,000 for fund transfers)
- **Maximum Amount:** Variable per e-wallet regulations
- **Processing:** Real-time
- **Settlement:** Real-time

## Complete Example

```php
class TopupController extends Controller
{
    public function topupWallet(Request $request)
    {
        $topupService = app(PakaiLinkTopupService::class);

        // Step 1: Inquiry customer to get sessionId
        $inquiry = $topupService->inquiryCustomer(
            customerNumber: $request->wallet_phone,
            productCode: strtoupper($request->wallet_type),
            amount: $request->amount
        );

        // Verify customer name
        $customerName = $inquiry['customerName'];

        // Step 2: Create topup using sessionId from inquiry
        $topup = $topupService->createTopup(TopupPaymentData::from([
            'amount' => $request->amount,
            'customer_number' => $request->wallet_phone,
            'product_code' => strtoupper($request->wallet_type),
            'session_id' => $inquiry['sessionId'], // From inquiry!
        ]));

        // Store topup record
        Topup::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'wallet_type' => $request->wallet_type,
            'wallet_phone' => $request->wallet_phone,
            'pakailink_ref' => $topup['partnerReferenceNo'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'pending',
            'customer_name' => $topup['customerName'],
            'reference' => $topup['partnerReferenceNo'],
            'message' => 'Topup is being processed',
        ]);
    }

    public function checkTopupStatus($topupId)
    {
        $topup = Topup::findOrFail($topupId);

        $topupService = app(PakaiLinkTopupService::class);
        $status = $topupService->inquiryStatus($topup->pakailink_ref);

        return response()->json([
            'status' => $status['latestTransactionStatus'],
            'completed' => $status['latestTransactionStatus'] === '00',
        ]);
    }
}
```

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2003800` | Successful | Success |
| `4003802` | Invalid Mandatory Field | Add missing fields |
| `4033802` | Exceeds Amount Limit | Check limits |
| `4033803` | Invalid Transaction | Check session ID |
| `4043811` | Invalid Account | Verify customer number |

---

**Flow:** Create topup → Processes in background → Callback received → Topup complete.
