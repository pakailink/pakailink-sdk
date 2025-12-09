<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\CreateVirtualAccountData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkVirtualAccountService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
        protected string $partnerServiceId,
    ) {}

    /**
     * Create Virtual Account.
     *
     * @return array API response
     */
    public function create(CreateVirtualAccountData $data): array
    {
        Log::channel('pakailink')->info('Creating Virtual Account', [
            'amount' => $data->amount,
            'bank_code' => $data->bankCode,
        ]);

        $requestData = $data->toApiPayload($this->partnerServiceId);

        try {
            $response = $this->client->post(
                '/snap/v1.0/transfer-va/create-va',
                $requestData
            );

            // Extract virtualAccountData from response
            $vaData = $response['virtualAccountData'] ?? [];

            Log::channel('pakailink')->info('Virtual Account created successfully', [
                'va_number' => $vaData['virtualAccountNo'] ?? null,
                'reference_no' => $vaData['partnerReferenceNo'] ?? null,
            ]);

            // Return flattened response with virtualAccountData fields
            return array_merge($response, $vaData);
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to create Virtual Account', [
                'error' => $e->getMessage(),
                'request' => $requestData,
            ]);

            throw new PakaiLinkTransactionException(
                "Failed to create Virtual Account: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Inquiry VA transaction status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from create request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring VA status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/transfer-va/create-va-status',
                $requestData
            );

            Log::channel('pakailink')->info('VA status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['transactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry VA status', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate unique reference number.
     */
    public function generateReferenceNo(): string
    {
        return 'VA-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
