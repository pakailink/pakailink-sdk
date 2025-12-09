<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\GenerateQrisData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkQrisService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Generate QRIS QR Code (MPM - Merchant Presented Mode).
     *
     * @return array API response
     */
    public function generateQris(GenerateQrisData $data): array
    {
        Log::channel('pakailink')->info('Generating QRIS', [
            'amount' => $data->amount,
            'merchant_id' => $data->merchantId,
        ]);

        $requestData = $data->toApiPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/qr/qr-mpm-generate',
                $requestData
            );

            Log::channel('pakailink')->info('QRIS generated successfully', [
                'nmid' => $response['nmid'] ?? null,
                'reference_no' => $response['referenceNo'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to generate QRIS', [
                'error' => $e->getMessage(),
                'request' => $requestData,
            ]);

            throw new PakaiLinkTransactionException(
                "Failed to generate QRIS: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Query QRIS transaction status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from generate request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring QRIS status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/qr/qr-mpm-status',
                $requestData
            );

            Log::channel('pakailink')->info('QRIS status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['transactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry QRIS status', [
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
        return 'QRIS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
