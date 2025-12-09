<?php

namespace PakaiLink\Services;

use Illuminate\Support\Facades\Log;
use PakaiLink\Data\Callbacks\EmoneyCallbackData;
use PakaiLink\Data\Callbacks\QrisCallbackData;
use PakaiLink\Data\Callbacks\RetailCallbackData;
use PakaiLink\Data\Callbacks\TopupCallbackData;
use PakaiLink\Data\Callbacks\TransferCallbackData;
use PakaiLink\Data\Callbacks\VirtualAccountCallbackData;
use PakaiLink\Events\CallbackReceived;
use PakaiLink\Events\EmoneyPaymentReceived;
use PakaiLink\Events\QrisPaymentReceived;
use PakaiLink\Events\RetailPaymentReceived;
use PakaiLink\Events\TopupCompleted;
use PakaiLink\Events\TransferCompleted;
use PakaiLink\Events\VirtualAccountPaid;
use PakaiLink\Exceptions\PakaiLinkSignatureException;

class PakaiLinkCallbackService
{
    public function __construct(
        private PakaiLinkSignatureService $signatureService,
    ) {}

    public function handleVirtualAccountCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'virtual_account');

        $data = VirtualAccountCallbackData::from($payload);

        event(new VirtualAccountPaid($data, $payload));

        Log::channel('pakailink')->info('Virtual Account callback processed', [
            'partner_reference_no' => $data->partnerReferenceNo,
            'amount' => $data->getAmount(),
            'status' => $data->latestTransactionStatus,
        ]);

        return $this->buildSuccessResponse($data->partnerReferenceNo);
    }

    public function handleQrisCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'qris');

        $data = QrisCallbackData::from($payload);

        event(new QrisPaymentReceived($data, $payload));

        Log::channel('pakailink')->info('QRIS callback processed', [
            'partner_reference_no' => $data->originalPartnerReferenceNo,
            'amount' => $data->getAmount(),
            'status' => $data->latestTransactionStatus,
        ]);

        return $this->buildSuccessResponse($data->originalPartnerReferenceNo);
    }

    public function handleEmoneyCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'emoney');

        $data = EmoneyCallbackData::from($payload);

        event(new EmoneyPaymentReceived($data, $payload));

        Log::channel('pakailink')->info('E-money callback processed', [
            'partner_reference_no' => $data->originalPartnerReferenceNo,
            'amount' => $data->getAmount(),
            'channel' => $data->channelId,
            'status' => $data->latestTransactionStatus,
        ]);

        return $this->buildSuccessResponse($data->originalPartnerReferenceNo);
    }

    public function handleTransferCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'transfer');

        $data = TransferCallbackData::from($payload);

        event(new TransferCompleted($data, $payload));

        Log::channel('pakailink')->info('Transfer callback processed', [
            'partner_reference_no' => $data->partnerReferenceNo,
            'amount' => $data->getAmount(),
            'bank_code' => $data->beneficiaryBankCode,
            'status' => $data->latestTransactionStatus,
        ]);

        return $this->buildSuccessResponse($data->partnerReferenceNo);
    }

    public function handleRetailCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'retail');

        $data = RetailCallbackData::from($payload);

        event(new RetailPaymentReceived($data, $payload));

        Log::channel('pakailink')->info('Retail callback processed', [
            'partner_reference_no' => $data->partnerReferenceNo,
            'customer_name' => $data->customerName,
            'paid_amount' => $data->getPaidAmountValue(),
            'status' => $data->paymentFlagStatus,
        ]);

        return $this->buildSuccessResponse($data->partnerReferenceNo);
    }

    public function handleTopupCallback(array $payload, string $signature, string $timestamp): array
    {
        $this->validateCallback($payload, $signature, $timestamp, 'topup');

        $data = TopupCallbackData::from($payload);

        event(new TopupCompleted($data, $payload));

        Log::channel('pakailink')->info('Topup callback processed', [
            'partner_reference_no' => $data->partnerReferenceNo,
            'account_name' => $data->accountName,
            'paid_amount' => $data->getPaidAmountValue(),
            'status' => $data->paymentFlagStatus,
        ]);

        return $this->buildSuccessResponse($data->partnerReferenceNo);
    }

    private function validateCallback(array $payload, string $signature, string $timestamp, string $type): void
    {
        event(new CallbackReceived($type, $payload, false, $signature));

        if (! $this->signatureService->validateCallbackSignature(
            $signature,
            json_encode($payload),
            $timestamp
        )) {
            Log::channel('pakailink')->error('Invalid callback signature', [
                'type' => $type,
                'signature' => $signature,
                'timestamp' => $timestamp,
            ]);

            throw new PakaiLinkSignatureException('Invalid callback signature');
        }

        event(new CallbackReceived($type, $payload, true, $signature));

        Log::channel('pakailink')->info('Callback signature validated', [
            'type' => $type,
        ]);
    }

    private function buildSuccessResponse(string $partnerReferenceNo): array
    {
        return [
            'responseCode' => '2000000',
            'responseMessage' => 'Success',
            'partnerReferenceNo' => $partnerReferenceNo,
        ];
    }

    public function buildErrorResponse(string $code, string $message, ?string $partnerReferenceNo = null): array
    {
        return [
            'responseCode' => $code,
            'responseMessage' => $message,
            'partnerReferenceNo' => $partnerReferenceNo,
        ];
    }
}
