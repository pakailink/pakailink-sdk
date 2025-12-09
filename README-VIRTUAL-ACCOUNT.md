# PakaiLink SDK - Virtual Account Guide

## Quick Start

### 1. Create Virtual Account

```php
use Pgpay\PakaiLink\Services\PakaiLinkVirtualAccountService;
use Pgpay\PakaiLink\Data\CreateVirtualAccountData;

$vaService = app(PakaiLinkVirtualAccountService::class);

$va = $vaService->create(CreateVirtualAccountData::from([
    'amount' => 100000,
    'customer_name' => 'John Doe',
    'bank_code' => '002', // BRI
]));

// Get VA number to display
$vaNumber = $va['virtualAccountData']['virtualAccountNo'];
$partnerRefNo = $va['virtualAccountData']['partnerReferenceNo']; // Store this!
```

### 2. Display VA Number to Customer

```php
return response()->json([
    'va_number' => $vaNumber,
    'bank_name' => 'BRI',
    'amount' => 100000,
    'expires_at' => $va['virtualAccountData']['expiredDate'],
]);
```

### 3. Check Payment Status

```php
$status = $vaService->inquiryStatus($partnerRefNo);

if ($status['latestTransactionStatus'] === '00') {
    // Payment received!
    $paidAmount = $status['additionalInfo']['nominalPaid']['value'];
    $received = $status['additionalInfo']['totalReceive']['value'];
}
```

### 4. Handle Callback

```php
use Pgpay\PakaiLink\Events\VirtualAccountPaid;

Event::listen(function (VirtualAccountPaid $event) {
    $data = $event->data;

    $partnerRefNo = $data->partnerReferenceNo;
    $paidAmount = $data->getPaidAmountValue();

    // Update your order
    Order::where('pakailink_ref', $partnerRefNo)->update([
        'status' => 'paid',
        'paid_amount' => $paidAmount,
    ]);
});
```

## Bank Codes

| Code | Bank Name |
|------|-----------|
| `002` | BRI |
| `008` | Mandiri |
| `009` | BNI |
| `014` | BCA |
| `022` | CIMB Niaga |
| `213` | BTPN |
| `451` | BSI |

## Advanced Options

### With Phone and Email

```php
$va = $vaService->create(CreateVirtualAccountData::from([
    'amount' => 150000,
    'customer_name' => 'Jane Doe',
    'bank_code' => '014', // BCA
    'virtual_account_phone' => '08123456789',
    'virtual_account_email' => 'jane@example.com',
]));
```

### Custom Expiry

```php
$va = $vaService->create(CreateVirtualAccountData::from([
    'amount' => 200000,
    'customer_name' => 'Bob Smith',
    'bank_code' => '008', // Mandiri
    'expired_date' => now()->addDays(7), // 7 days validity
]));
```

### Custom Customer Number

```php
$va = $vaService->create(CreateVirtualAccountData::from([
    'amount' => 100000,
    'customer_name' => 'Alice Wong',
    'bank_code' => '009', // BNI
    'customer_no' => '1234567890', // Max 20 chars
]));
```

## Response Structure

### Create Response
```json
{
  "responseCode": "2002700",
  "responseMessage": "Successful",
  "virtualAccountData": {
    "customerNo": "131857418122353",
    "virtualAccountNo": "1234010000049477",
    "partnerReferenceNo": "...",
    "expiredDate": "2025-07-05T17:14:50+07:00",
    "totalAmount": {
      "currency": "IDR",
      "value": "100000.00"
    },
    "additionalInfo": {
      "bankCode": "002",
      "callbackUrl": "http://...",
      "referenceNo": "VAI175162049169326803960721"
    }
  }
}
```

### Inquiry Response
```json
{
  "responseCode": "2003300",
  "responseMessage": "Successful",
  "latestTransactionStatus": "00",
  "amount": {
    "value": "100000.00",
    "currency": "IDR"
  },
  "additionalInfo": {
    "nominalPaid": {
      "value": "100000.00",
      "currency": "IDR"
    },
    "serviceFee": {
      "value": "4100.00",
      "currency": "IDR"
    },
    "totalReceive": {
      "value": "95900.00",
      "currency": "IDR"
    }
  }
}
```

## Transaction Limits

- **Minimum Amount:** IDR 10,000
- **Maximum Amount:** IDR 2,000,000,000
- **Validity:** Default 24 hours (customizable)
- **Settlement:** Real-time to PakaiLink balance

## Complete Example

```php
class PaymentController extends Controller
{
    public function createVirtualAccount(Request $request)
    {
        $vaService = app(PakaiLinkVirtualAccountService::class);

        // Create VA
        $va = $vaService->create(CreateVirtualAccountData::from([
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'bank_code' => $request->bank_code,
            'virtual_account_phone' => $request->phone,
        ]));

        // Store in database
        Payment::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'va_number' => $va['virtualAccountData']['virtualAccountNo'],
            'pakailink_ref' => $va['virtualAccountData']['partnerReferenceNo'],
            'bank_code' => $request->bank_code,
            'status' => 'pending',
            'expires_at' => $va['virtualAccountData']['expiredDate'],
        ]);

        return response()->json([
            'va_number' => $va['virtualAccountData']['virtualAccountNo'],
            'bank' => $this->getBankName($request->bank_code),
            'amount' => $request->amount,
        ]);
    }

    public function checkStatus($paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        $vaService = app(PakaiLinkVirtualAccountService::class);
        $status = $vaService->inquiryStatus($payment->pakailink_ref);

        return response()->json([
            'paid' => $status['latestTransactionStatus'] === '00',
            'status' => $status['latestTransactionStatus'],
        ]);
    }
}
```

## Error Codes

| Code | Message | Solution |
|------|---------|----------|
| `2002700` | Successful | Success |
| `4002701` | Invalid Field Format | Check field lengths |
| `4002702` | Invalid Mandatory Field | Add missing required fields |
| `4032702` | Exceeds Amount Limit | Check min/max limits |
| `4042703` | Bank Not Supported | Use valid bank code |
| `4092701` | Duplicate Reference | Use unique reference |

---

**Simple:** Create VA → Display number → Customer pays → Callback received → Order complete.
