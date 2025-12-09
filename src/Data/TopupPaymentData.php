<?php

namespace PakaiLink\Data;

class TopupPaymentData
{
    public function __construct(
        public float $amount,
        public string $customerNumber,
        public string $productCode,
        public string $sessionId,
        public ?string $partnerReferenceNo = null,
        public array $additionalInfo = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            amount: $data['amount'],
            customerNumber: $data['customerNumber'] ?? $data['customer_number'],
            productCode: $data['productCode'] ?? $data['product_code'],
            sessionId: $data['sessionId'] ?? $data['session_id'],
            partnerReferenceNo: $data['partnerReferenceNo'] ?? $data['partner_reference_no'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? $data['additional_info'] ?? [],
        );
    }

    public function toApiPayload(): array
    {
        return [
            'partnerReferenceNo' => $this->partnerReferenceNo ?? $this->generateReferenceNo(),
            'customerNumber' => $this->customerNumber,
            'productCode' => $this->productCode,
            'sessionId' => $this->sessionId,
            'amount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => array_merge([
                'callbackUrl' => config('pakailink.callback.base_url').'/topup',
            ], $this->additionalInfo),
        ];
    }

    protected function generateReferenceNo(): string
    {
        return \Illuminate\Support\Str::random(40);
    }
}
