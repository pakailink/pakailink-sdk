<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\CreateRetailPaymentData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkRetailService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Create Retail payment code.
     *
     * @return array API response
     */
    public function createPayment(CreateRetailPaymentData $data): array
    {
        Log::channel('pakailink')->info('Creating Retail payment', [
            'product_code' => $data->productCode,
            'amount' => $data->amount,
        ]);

        $requestData = $data->toApiPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/payment/modern-retail',
                $requestData
            );

            Log::channel('pakailink')->info('Retail payment created successfully', [
                'payment_code' => $response['paymentData']['paymentCode'] ?? null,
                'reference_no' => $response['paymentData']['referenceNo'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to create Retail payment', [
                'error' => $e->getMessage(),
                'request' => $requestData,
            ]);

            throw new PakaiLinkTransactionException(
                "Failed to create Retail payment: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Inquiry Retail transaction status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from create request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring Retail status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/payment/modern-retail/status',
                $requestData
            );

            Log::channel('pakailink')->info('Retail status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['transactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry Retail status', [
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
        return 'RTL-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
