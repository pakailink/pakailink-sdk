<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\TopupPaymentData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkTopupService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Inquiry customer account before topup.
     *
     * @return array API response with sessionId
     */
    public function inquiryCustomer(string $customerNumber, string $productCode, float $amount, ?string $partnerReferenceNo = null): array
    {
        Log::channel('pakailink')->info('Inquiring customer for topup', [
            'customer_number' => $customerNumber,
            'product_code' => $productCode,
        ]);

        $requestData = [
            'partnerReferenceNo' => $partnerReferenceNo ?? \Illuminate\Support\Str::random(40),
            'customerNumber' => $customerNumber,
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => [
                'productCode' => $productCode,
            ],
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/account-inquiry',
                $requestData
            );

            Log::channel('pakailink')->info('Customer inquiry successful', [
                'session_id' => $response['sessionId'] ?? null,
                'customer_name' => $response['customerName'] ?? 'unknown',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry customer', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Customer Topup payment.
     *
     * @return array API response
     */
    public function createTopup(TopupPaymentData $data): array
    {
        Log::channel('pakailink')->info('Creating Customer Topup', [
            'product_code' => $data->productCode,
            'customer_number' => $data->customerNumber,
            'amount' => $data->amount,
        ]);

        $requestData = $data->toApiPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/topup',
                $requestData
            );

            Log::channel('pakailink')->info('Customer Topup created successfully', [
                'reference_no' => $response['referenceNo'] ?? null,
                'customer_name' => $response['customerName'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to create Customer Topup', [
                'error' => $e->getMessage(),
                'request' => $requestData,
            ]);

            throw new PakaiLinkTransactionException(
                "Failed to create Customer Topup: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Inquiry Customer Topup transaction status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from create request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring Customer Topup status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/topup/status',
                $requestData
            );

            Log::channel('pakailink')->info('Customer Topup status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['latestTransactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry Customer Topup status', [
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
        return 'TOP-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
