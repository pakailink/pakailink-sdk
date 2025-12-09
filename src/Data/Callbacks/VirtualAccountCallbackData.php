<?php

namespace PakaiLink\Data\Callbacks;

class VirtualAccountCallbackData extends BaseCallbackData
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
        public ?string $paymentFlagReason = null,
    ) {
        parent::__construct(
            partnerServiceId: $partnerServiceId,
            customerNo: $customerNo,
            virtualAccountNo: $virtualAccountNo,
            virtualAccountName: $virtualAccountName,
            partnerReferenceNo: $partnerReferenceNo,
            amount: $amount,
            latestTransactionStatus: $latestTransactionStatus,
            transactionStatusDesc: $transactionStatusDesc,
            inquiryRequestId: $inquiryRequestId,
            additionalInfo: $additionalInfo,
            trxDateTime: $trxDateTime,
        );
    }

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
            paymentFlagReason: $data['paymentFlagReason'] ?? null,
        );
    }

    public function getBankCode(): ?string
    {
        return $this->additionalInfo['bankCd'] ?? null;
    }

    public function getPaymentFlagReason(): ?string
    {
        return $this->paymentFlagReason;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'paymentFlagReason' => $this->paymentFlagReason,
        ]);
    }
}
