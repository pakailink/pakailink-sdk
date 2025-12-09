# PakaiLink SDK - Transfer to Bank Guide

## Quick Start

### 1. Inquiry Bank Account (Required First)

```php
use Pgpay\PakaiLink\Services\PakaiLinkTransferService;
use Pgpay\PakaiLink\Data\TransferToBankData;

$transferService = app(PakaiLinkTransferService::class);

$data = TransferToBankData::from([
    'beneficiary_bank_code' => '014', // BCA
    'beneficiary_account_number' => '1234567890',
    'amount' => 100000,
]);

// First, inquiry to verify account
$inquiry = $transferService->inquiryTransfer($data);

// Verify account details
$accountName = $inquiry['beneficiaryAccountName']; // "Fathan Devani"
$bankName = $inquiry['beneficiaryBankName']; // "BANK BCA"
$sessionId = $inquiry['sessionId']; // Save this for transfer!
```

### 2. Execute Transfer

```php
// Use the sessionId from inquiry
$transfer = $transferService->transferToBank(TransferToBankData::from([
    'beneficiary_bank_code' => '014',
    'beneficiary_account_number' => '1234567890',
    'amount' => 100000,
    'session_id' => $sessionId, // From inquiry
]));

$partnerRefNo = $transfer['partnerReferenceNo']; // Store this!
$status = $transfer['additionalInfo']['transactionStatus']; // Usually '03' (Pending)
```

### 3. Check Transfer Status

```php
$status = $transferService->inquiryStatus($partnerRefNo);

// Check status
if ($status['latestTransactionStatus'] === '00') {
    // Transfer successful!
} elseif ($status['latestTransactionStatus'] === '03') {
    // Still pending
} elseif ($status['latestTransactionStatus'] === '06') {
    // Transfer failed
}
```

### 4. Handle Callback

```php
use Pgpay\PakaiLink\Events\TransferCompleted;

Event::listen(function (TransferCompleted $event) {
    $data = $event->data;

    if ($data->isSuccessful()) {
        // Transfer completed
        Withdrawal::where('pakailink_ref', $data->partnerReferenceNo)
            ->update(['status' => 'completed']);
    }
});
```

## Bank Codes

| Code  | Bank Name  |
|-------|------------|
| `002` | BRI        |
| `008` | Mandiri    |
| `009` | BNI        |
| `014` | BCA        |
| `022` | CIMB Niaga |
| `213` | BTPN       |
| `451` | BSI        |

## Advanced Options

### With Remark

```php
$transfer = $transferService->transferToBank(TransferToBankData::from([
    'beneficiary_bank_code' => '002',
    'beneficiary_account_number' => '9876543210',
    'amount' => 500000,
    'session_id' => $sessionId,
    'remark' => 'Payment for invoice #INV-001',
]));
```

## Transaction Limits

- **Minimum Amount:** IDR 10,000
- **Maximum Amount:** IDR 50,000,000
- **Processing:** Real-time
- **Settlement:** Real-time

## Complete Example

```php
class WithdrawalController extends Controller
{
    public function withdraw(Request $request)
    {
        $transferService = app(PakaiLinkTransferService::class);

        // Step 1: Inquiry account
        $inquiry = $transferService->inquiryTransfer(TransferToBankData::from([
            'beneficiary_bank_code' => $request->bank_code,
            'beneficiary_account_number' => $request->account_number,
            'amount' => $request->amount,
        ]));

        // Step 2: Confirm with user
        if ($inquiry['beneficiaryAccountName'] !== $request->expected_name) {
            return response()->json([
                'error' => 'Account name mismatch',
                'found' => $inquiry['beneficiaryAccountName'],
            ], 400);
        }

        // Step 3: Execute transfer
        $transfer = $transferService->transferToBank(TransferToBankData::from([
            'beneficiary_bank_code' => $request->bank_code,
            'beneficiary_account_number' => $request->account_number,
            'amount' => $request->amount,
            'session_id' => $inquiry['sessionId'],
            'remark' => "Withdrawal for user #{$request->user()->id}",
        ]));

        // Step 4: Store withdrawal
        Withdrawal::create([
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'bank_code' => $request->bank_code,
            'account_number' => $request->account_number,
            'account_name' => $inquiry['beneficiaryAccountName'],
            'pakailink_ref' => $transfer['partnerReferenceNo'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'pending',
            'reference' => $transfer['partnerReferenceNo'],
        ]);
    }
}
```

## Error Codes

| Code      | Message                 | Solution               |
|-----------|-------------------------|------------------------|
| `2004300` | Successful              | Success                |
| `4004302` | Invalid Mandatory Field | Add missing fields     |
| `4034302` | Exceeds Amount Limit    | Check min 10k, max 50M |
| `4044311` | Invalid Account         | Verify account number  |
| `4094301` | Duplicate Reference     | Use unique reference   |

---

**Important:** Always do inquiry first to verify account, then use the sessionId for transfer!
