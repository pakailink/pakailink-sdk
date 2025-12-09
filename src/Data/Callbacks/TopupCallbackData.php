<?php

namespace PakaiLink\Data\Callbacks;

class TopupCallbackData
{
    public function __construct(
        public string $partnerReferenceNo,
        public string $accountNumber,
        public string $accountName,
        public string $referenceNo,
        public string $paymentFlagStatus,
        public array $paymentFlagReason,
        public array $paidAmount,
        public array $feeAmount,
        public ?array $additionalInfo = null,
    ) {}

    public static function from(array $data): static
    {
        $transactionData = $data['transactionData'] ?? $data;

        return new static(
            partnerReferenceNo: $transactionData['partnerReferenceNo'] ?? '',
            accountNumber: $transactionData['accountNumber'] ?? '',
            accountName: $transactionData['accountName'] ?? '',
            referenceNo: $transactionData['referenceNo'] ?? '',
            paymentFlagStatus: $transactionData['paymentFlagStatus'] ?? '',
            paymentFlagReason: $transactionData['paymentFlagReason'] ?? [],
            paidAmount: $transactionData['paidAmount'] ?? [],
            feeAmount: $transactionData['feeAmount'] ?? [],
            additionalInfo: $transactionData['additionalInfo'] ?? null,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->paymentFlagStatus === '00';
    }

    public function getPaidAmountValue(): float
    {
        return (float) ($this->paidAmount['value'] ?? 0);
    }

    public function getFeeAmountValue(): float
    {
        return (float) ($this->feeAmount['value'] ?? 0);
    }

    public function getBalance(): ?float
    {
        return isset($this->additionalInfo['balance']['value'])
            ? (float) $this->additionalInfo['balance']['value']
            : null;
    }

    public function toArray(): array
    {
        return [
            'partnerReferenceNo' => $this->partnerReferenceNo,
            'accountNumber' => $this->accountNumber,
            'accountName' => $this->accountName,
            'referenceNo' => $this->referenceNo,
            'paymentFlagStatus' => $this->paymentFlagStatus,
            'paymentFlagReason' => $this->paymentFlagReason,
            'paidAmount' => $this->paidAmount,
            'feeAmount' => $this->feeAmount,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
