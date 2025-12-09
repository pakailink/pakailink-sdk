<?php

namespace PakaiLink\Data\Callbacks;

class RetailCallbackData
{
    public function __construct(
        public string $partnerReferenceNo,
        public string $customerNo,
        public string $customerName,
        public string $callbackType,
        public string $paymentFlagStatus,
        public array $paymentFlagReason,
        public array $paidAmount,
        public array $feeAmount,
        public array $creditBalance,
        public ?array $additionalInfo = null,
    ) {}

    public static function from(array $data): static
    {
        $transactionData = $data['transactionData'] ?? $data;

        return new static(
            partnerReferenceNo: $transactionData['partnerReferenceNo'] ?? '',
            customerNo: $transactionData['customerNo'] ?? '',
            customerName: $transactionData['customerName'] ?? '',
            callbackType: $transactionData['callbackType'] ?? 'settlement',
            paymentFlagStatus: $transactionData['paymentFlagStatus'] ?? '',
            paymentFlagReason: $transactionData['paymentFlagReason'] ?? [],
            paidAmount: $transactionData['paidAmount'] ?? [],
            feeAmount: $transactionData['feeAmount'] ?? [],
            creditBalance: $transactionData['creditBalance'] ?? [],
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

    public function getCreditBalanceValue(): float
    {
        return (float) ($this->creditBalance['value'] ?? 0);
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
            'customerNo' => $this->customerNo,
            'customerName' => $this->customerName,
            'callbackType' => $this->callbackType,
            'paymentFlagStatus' => $this->paymentFlagStatus,
            'paymentFlagReason' => $this->paymentFlagReason,
            'paidAmount' => $this->paidAmount,
            'feeAmount' => $this->feeAmount,
            'creditBalance' => $this->creditBalance,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
