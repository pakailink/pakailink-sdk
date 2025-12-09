<?php

namespace PakaiLink\Data\Callbacks;

class TransferCallbackData
{
    public function __construct(
        public string $partnerReferenceNo,
        public string $referenceNo,
        public string $beneficiaryBankCode,
        public string $beneficiaryAccountNo,
        public string $beneficiaryAccountName,
        public array $amount,
        public string $latestTransactionStatus,
        public string $transactionStatusDesc,
        public ?string $transactionDate = null,
        public ?array $additionalInfo = null,
    ) {}

    public static function from(array $data): static
    {
        return new static(
            partnerReferenceNo: $data['partnerReferenceNo'] ?? '',
            referenceNo: $data['referenceNo'] ?? '',
            beneficiaryBankCode: $data['beneficiaryBankCode'] ?? '',
            beneficiaryAccountNo: $data['beneficiaryAccountNo'] ?? '',
            beneficiaryAccountName: $data['beneficiaryAccountName'] ?? '',
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

    public function toArray(): array
    {
        return [
            'partnerReferenceNo' => $this->partnerReferenceNo,
            'referenceNo' => $this->referenceNo,
            'beneficiaryBankCode' => $this->beneficiaryBankCode,
            'beneficiaryAccountNo' => $this->beneficiaryAccountNo,
            'beneficiaryAccountName' => $this->beneficiaryAccountName,
            'amount' => $this->amount,
            'latestTransactionStatus' => $this->latestTransactionStatus,
            'transactionStatusDesc' => $this->transactionStatusDesc,
            'transactionDate' => $this->transactionDate,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
