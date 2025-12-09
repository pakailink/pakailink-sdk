# PakaiLink SDK - Balance Management Guide

## Configuration

Set your PakaiLink account number in `.env`:

```env
PAKAILINK_ACCOUNT_NO=your_account_number
```

**Note:** Balance inquiry and history features require:
- Valid PakaiLink account number from PakaiLink dashboard
- May not be available in sandbox environment
- Contact PakaiLink support to enable these features

## Balance Inquiry

### Get Current Balance

```php
use Pgpay\PakaiLink\Services\PakaiLinkBalanceService;

$balanceService = app(PakaiLinkBalanceService::class);

// Query your PakaiLink account balance (uses PAKAILINK_ACCOUNT_NO from config)
$balance = $balanceService->inquiryBalance();

// Get balance info
$accountNo = $balance['accountNo'];
$accountName = $balance['name'];

foreach ($balance['accountInfo'] as $info) {
    $balanceType = $info['balanceType']; // 'Balance', 'CASH', 'QRIS', etc.
    $active = $info['activeBalance']['value'];
    $freeze = $info['freezeBalance']['value'];
    $pending = $info['pendingBalance']['value'];
    $status = $info['status']; // 0001=Active
}
```

### Query Specific Balance Types

```php
$balance = $balanceService->inquiryBalance(
    balanceTypes: ['Balance', 'CASH', 'QRIS']
);
```

### Query Specific Account

```php
$balance = $balanceService->inquiryBalance(
    balanceTypes: ['Balance'],
    accountNo: '2536265359300003'
);
```

## Balance History

### Get Transaction History

```php
$history = $balanceService->balanceHistory(
    fromDateTime: '2025-11-01T00:00:00+07:00',
    toDateTime: '2025-11-30T23:59:59+07:00',
    pageSize: 20,
    pageNumber: 1
);

foreach ($history['detailData'] as $transaction) {
    $dateTime = $transaction['dateTime'];
    $amount = $transaction['amount']['value']; // Can be negative (debit)
    $type = $transaction['type']; // CREDIT or DEBIT
    $remark = $transaction['remark'];
    $status = $transaction['status']; // SUCCESS, FAILED, etc.

    // Additional info
    $trxId = $transaction['additionalInfo']['trxId'];
    $startBalance = $transaction['additionalInfo']['startBalance'];
    $endBalance = $transaction['additionalInfo']['endBalance'];
}
```

### Pagination Example

```php
$page = 1;
$perPage = 50;

do {
    $history = $balanceService->balanceHistory(
        fromDateTime: now()->subDays(7)->format('Y-m-d\TH:i:sP'),
        toDateTime: now()->format('Y-m-d\TH:i:sP'),
        pageSize: $perPage,
        pageNumber: $page
    );

    foreach ($history['detailData'] as $transaction) {
        // Process transaction
        processTransaction($transaction);
    }

    $page++;
} while (count($history['detailData']) === $perPage);
```

## Complete Example

```php
class BalanceController extends Controller
{
    public function getBalance()
    {
        $balanceService = app(PakaiLinkBalanceService::class);

        $balance = $balanceService->inquiryBalance();

        $balances = [];
        foreach ($balance['accountInfo'] as $info) {
            $balances[$info['balanceType']] = [
                'active' => $info['activeBalance']['value'],
                'freeze' => $info['freezeBalance']['value'],
                'pending' => $info['pendingBalance']['value'],
                'status' => $info['status'],
            ];
        }

        return response()->json([
            'account' => $balance['accountNo'],
            'name' => $balance['name'],
            'balances' => $balances,
        ]);
    }

    public function getHistory(Request $request)
    {
        $balanceService = app(PakaiLinkBalanceService::class);

        $history = $balanceService->balanceHistory(
            fromDateTime: $request->from ?? now()->subDays(30)->format('Y-m-d\T00:00:00+07:00'),
            toDateTime: $request->to ?? now()->format('Y-m-d\T23:59:59+07:00'),
            pageSize: $request->per_page ?? 20,
            pageNumber: $request->page ?? 1
        );

        return response()->json($history);
    }
}
```

## Response Codes

### Balance Inquiry
| Code | Message | Status |
|------|---------|--------|
| `2001100` | Successful | Success |
| `4001102` | Invalid Mandatory Field | Missing fields |
| `4011100` | Unauthorized | Check auth |

### Balance History
| Code | Message | Status |
|------|---------|--------|
| `2001200` | Successful | Success |
| `4001202` | Invalid Mandatory Field | Missing fields |
| `4011200` | Unauthorized | Check auth |

---

**Simple:** Check balance anytime, view transaction history with pagination.
