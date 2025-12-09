<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\CreateEmoneyPaymentData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkEmoneyService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Create E-money payment.
     *
     * @return array API response
     */
    public function createPayment(CreateEmoneyPaymentData $data): array
    {
        Log::channel('pakailink')->info('Creating E-money payment', [
            'product_code' => $data->productCode,
            'customer_id' => $data->customerId,
            'amount' => $data->amount,
        ]);

        $requestData = $data->toApiPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/payment/emoney',
                $requestData
            );

            Log::channel('pakailink')->info('E-money payment created successfully', [
                'reference_no' => $response['referenceNo'] ?? null,
                'web_redirect_url' => $response['webRedirectUrl'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to create E-money payment', [
                'error' => $e->getMessage(),
                'request' => $requestData,
            ]);

            throw new PakaiLinkTransactionException(
                "Failed to create E-money payment: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Inquiry E-money transaction status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from create request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring E-money status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/payment/emoney-status',
                $requestData
            );

            Log::channel('pakailink')->info('E-money status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['transactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry E-money status', [
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
        return 'EMY-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
