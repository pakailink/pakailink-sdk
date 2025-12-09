<?php

namespace PakaiLink\Data\Callbacks;

class QrisCallbackData
{
    public function __construct(
        public string $originalPartnerReferenceNo,
        public string $originalReferenceNo,
        public string $merchantId,
        public string $subMerchantId,
        public string $externalStoreId,
        public array $amount,
        public string $latestTransactionStatus,
        public string $transactionStatusDesc,
        public ?string $transactionDate = null,
        public ?array $additionalInfo = null,
    ) {}

    public static function from(array $data): static
    {
        return new static(
            originalPartnerReferenceNo: $data['originalPartnerReferenceNo'] ?? '',
            originalReferenceNo: $data['originalReferenceNo'] ?? '',
            merchantId: $data['merchantId'] ?? '',
            subMerchantId: $data['subMerchantId'] ?? '',
            externalStoreId: $data['externalStoreId'] ?? '',
            amount: $data['amount'] ?? [],
            latestTransactionStatus: $data['latestTransactionStatus'] ?? '',
            transactionStatusDesc: $data['transactionStatusDesc'] ?? '',
            transactionDate: $data['transactionDate'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? null,
        );
    }

    public function getAmount(): float
    {
        return (float) ($this->amount['value'] ?? 0);
    }

    public function getCurrency(): string
    {
        return $this->amount['currency'] ?? 'IDR';
    }

    public function isSuccess(): bool
    {
        return $this->latestTransactionStatus === '00';
    }

    public function isPending(): bool
    {
        return $this->latestTransactionStatus === '01';
    }

    public function isFailed(): bool
    {
        return ! $this->isSuccess() && ! $this->isPending();
    }

    public function getQrisContent(): ?string
    {
        return $this->additionalInfo['qrContent'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'originalPartnerReferenceNo' => $this->originalPartnerReferenceNo,
            'originalReferenceNo' => $this->originalReferenceNo,
            'merchantId' => $this->merchantId,
            'subMerchantId' => $this->subMerchantId,
            'externalStoreId' => $this->externalStoreId,
            'amount' => $this->amount,
            'latestTransactionStatus' => $this->latestTransactionStatus,
            'transactionStatusDesc' => $this->transactionStatusDesc,
            'transactionDate' => $this->transactionDate,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
