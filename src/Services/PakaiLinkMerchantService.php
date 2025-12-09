<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;

class PakaiLinkMerchantService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Register QRIS merchant.
     *
     * @return array API response
     */
    public function registerQrisMerchant(array $merchantData, array $ownerData, ?string $partnerReferenceNo = null): array
    {
        Log::channel('pakailink')->info('Registering QRIS merchant', [
            'merchant_name' => $merchantData['merchantName'] ?? null,
        ]);

        $requestData = [
            'partnerReferenceNo' => $partnerReferenceNo ?? \Illuminate\Support\Str::random(40),
            'merchantData' => $merchantData,
            'ownerData' => $ownerData,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/registration/qris',
                $requestData
            );

            Log::channel('pakailink')->info('QRIS merchant registered successfully', [
                'merchant_name' => $response['detailData']['merchantName'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to register QRIS merchant', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Register DANA merchant.
     *
     * @return array API response
     */
    public function registerDanaMerchant(array $merchantData, array $ownerData, ?string $partnerReferenceNo = null): array
    {
        Log::channel('pakailink')->info('Registering DANA merchant', [
            'merchant_name' => $merchantData['merchantName'] ?? null,
        ]);

        $requestData = [
            'partnerReferenceNo' => $partnerReferenceNo ?? \Illuminate\Support\Str::random(40),
            'merchantData' => $merchantData,
            'ownerData' => $ownerData,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/registration/dana',
                $requestData
            );

            Log::channel('pakailink')->info('DANA merchant registered successfully', [
                'merchant_name' => $response['detailData']['merchantName'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to register DANA merchant', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
