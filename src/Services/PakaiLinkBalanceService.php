<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;

class PakaiLinkBalanceService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Get balance inquiry.
     *
     * @param  array  $balanceTypes  Balance types to query (e.g., ['Balance', 'CASH', 'QRIS'])
     * @param  string|null  $accountNo  Account number (defaults to config value)
     * @return array API response with balance information
     */
    public function inquiryBalance(array $balanceTypes = ['Balance'], ?string $accountNo = null): array
    {
        $accountNo = $accountNo ?? config('pakailink.credentials.account_no');

        Log::channel('pakailink')->info('Inquiring balance', [
            'balance_types' => $balanceTypes,
            'account_no' => $accountNo,
        ]);

        $requestData = [
            'partnerReferenceNo' => \Illuminate\Support\Str::random(40),
            'balanceTypes' => $balanceTypes,
        ];

        if ($accountNo) {
            $requestData['accountNo'] = $accountNo;
        }

        try {
            $response = $this->client->post(
                '/snap/v1.0/balance-inquiry',
                $requestData
            );

            Log::channel('pakailink')->info('Balance inquiry successful', [
                'account_no' => $response['accountNo'] ?? null,
                'name' => $response['name'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry balance', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get balance history.
     *
     * @param  string  $fromDateTime  Start datetime (YYYY-MM-DDTHH:mm:ss+07:00)
     * @param  string  $toDateTime  End datetime (YYYY-MM-DDTHH:mm:ss+07:00)
     * @param  int  $pageSize  Records per page
     * @param  int  $pageNumber  Page number to retrieve
     * @return array API response with transaction history
     */
    public function balanceHistory(string $fromDateTime, string $toDateTime, int $pageSize = 10, int $pageNumber = 1): array
    {
        Log::channel('pakailink')->info('Getting balance history', [
            'from' => $fromDateTime,
            'to' => $toDateTime,
            'page' => $pageNumber,
        ]);

        $requestData = [
            'partnerReferenceNo' => \Illuminate\Support\Str::random(40),
            'fromDateTime' => $fromDateTime,
            'toDateTime' => $toDateTime,
            'pageSize' => (string) $pageSize,
            'pageNumber' => (string) $pageNumber,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/balance-history',
                $requestData
            );

            Log::channel('pakailink')->info('Balance history retrieved', [
                'records' => count($response['detailData'] ?? []),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to get balance history', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
