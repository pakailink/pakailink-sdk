<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PakaiLink\Data\TransferToBankData;
use PakaiLink\Exceptions\PakaiLinkTransactionException;

class PakaiLinkTransferService
{
    public function __construct(
        protected PakaiLinkHttpClient $client,
    ) {}

    /**
     * Inquiry bank account before transfer.
     *
     * @return array API response
     */
    public function inquiryTransfer(TransferToBankData $data): array
    {
        Log::channel('pakailink')->info('Inquiring bank transfer', [
            'bank_code' => $data->beneficiaryBankCode,
            'account_number' => $data->beneficiaryAccountNumber,
        ]);

        $requestData = $data->toInquiryPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/bank-account-inquiry',
                $requestData
            );

            Log::channel('pakailink')->info('Transfer inquiry successful', [
                'account_name' => $response['beneficiaryAccountName'] ?? 'unknown',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry transfer', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute bank transfer.
     *
     * @return array API response
     */
    public function transferToBank(TransferToBankData $data): array
    {
        Log::channel('pakailink')->info('Executing bank transfer', [
            'bank_code' => $data->beneficiaryBankCode,
            'amount' => $data->amount,
        ]);

        $requestData = $data->toApiPayload();

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/transfer-bank',
                $requestData
            );

            Log::channel('pakailink')->info('Bank transfer executed successfully', [
                'reference_no' => $response['referenceNo'] ?? null,
                'response_code' => $response['responseCode'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Bank transfer failed', [
                'error' => $e->getMessage(),
            ]);

            throw new PakaiLinkTransactionException(
                "Bank transfer failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Inquiry transfer status.
     *
     * @param  string  $originalPartnerReferenceNo  Partner reference number from transfer request
     * @return array API response with transaction status
     */
    public function inquiryStatus(string $originalPartnerReferenceNo): array
    {
        Log::channel('pakailink')->debug('Inquiring transfer status', [
            'partner_reference_no' => $originalPartnerReferenceNo,
        ]);

        $requestData = [
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        try {
            $response = $this->client->post(
                '/snap/v1.0/emoney/transfer-bank/status',
                $requestData
            );

            Log::channel('pakailink')->info('Transfer status inquiry successful', [
                'status' => $response['latestTransactionStatus'] ?? 'unknown',
                'status_desc' => $response['latestTransactionStatusDesc'] ?? '',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('pakailink')->error('Failed to inquiry transfer status', [
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
        return 'TRF-'.now()->format('YmdHis').'-'.strtoupper(Str::random(8));
    }
}
