<?php

namespace PakaiLink\Data\Callbacks;

use Carbon\Carbon;

abstract class BaseCallbackData
{
    public function __construct(
        public string $partnerServiceId,
        public string $customerNo,
        public string $virtualAccountNo,
        public string $virtualAccountName,
        public string $partnerReferenceNo,
        public array $amount,
        public string $latestTransactionStatus,
        public string $transactionStatusDesc,
        public ?string $inquiryRequestId = null,
        public ?array $additionalInfo = null,
        public ?string $trxDateTime = null,
    ) {}

    public static function from(array $data): static
    {
        return new static(
            partnerServiceId: $data['partnerServiceId'] ?? '',
            customerNo: $data['customerNo'] ?? '',
            virtualAccountNo: $data['virtualAccountNo'] ?? '',
            virtualAccountName: $data['virtualAccountName'] ?? '',
            partnerReferenceNo: $data['partnerReferenceNo'] ?? '',
            amount: $data['amount'] ?? [],
            latestTransactionStatus: $data['latestTransactionStatus'] ?? '',
            transactionStatusDesc: $data['transactionStatusDesc'] ?? '',
            inquiryRequestId: $data['inquiryRequestId'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? null,
            trxDateTime: $data['trxDateTime'] ?? null,
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

    public function getTransactionDateTime(): ?Carbon
    {
        if (! $this->trxDateTime) {
            return null;
        }

        return Carbon::parse($this->trxDateTime);
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
            'partnerServiceId' => $this->partnerServiceId,
            'customerNo' => $this->customerNo,
            'virtualAccountNo' => $this->virtualAccountNo,
            'virtualAccountName' => $this->virtualAccountName,
            'partnerReferenceNo' => $this->partnerReferenceNo,
            'amount' => $this->amount,
            'latestTransactionStatus' => $this->latestTransactionStatus,
            'transactionStatusDesc' => $this->transactionStatusDesc,
            'inquiryRequestId' => $this->inquiryRequestId,
            'additionalInfo' => $this->additionalInfo,
            'trxDateTime' => $this->trxDateTime,
        ];
    }
}
