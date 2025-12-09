<?php

namespace PakaiLink\Data;

class CreateRetailPaymentData
{
    public function __construct(
        public float $amount,
        public string $customerId,
        public string $customerName,
        public string $productCode,
        public ?string $partnerReferenceNo = null,
        public ?string $customerPhone = null,
        public ?string $customerEmail = null,
        public ?string $expiredDate = null,
        public ?string $remark = null,
        public array $additionalInfo = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            amount: $data['amount'],
            customerId: $data['customerId'] ?? $data['customer_id'],
            customerName: $data['customerName'] ?? $data['customer_name'],
            productCode: $data['productCode'] ?? $data['product_code'],
            partnerReferenceNo: $data['partnerReferenceNo'] ?? $data['partner_reference_no'] ?? null,
            customerPhone: $data['customerPhone'] ?? $data['customer_phone'] ?? null,
            customerEmail: $data['customerEmail'] ?? $data['customer_email'] ?? null,
            expiredDate: $data['expiredDate'] ?? $data['expired_date'] ?? null,
            remark: $data['remark'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? $data['additional_info'] ?? [],
        );
    }

    public function toApiPayload(): array
    {
        // Format expiredDate as per SNAP API: YYYY-MM-DDTHH:mm:ss+07:00
        $expiredDate = $this->expiredDate
            ? now()->parse($this->expiredDate)->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP')
            : now()->timezone('Asia/Jakarta')->addHours(24)->format('Y-m-d\TH:i:sP');

        return [
            'partnerReferenceNo' => $this->partnerReferenceNo ?? $this->generateReferenceNo(),
            'customerId' => $this->customerId,
            'customerName' => $this->customerName,
            'customerPhone' => $this->customerPhone,
            'customerEmail' => $this->customerEmail,
            'expiredDate' => $expiredDate,
            'totalAmount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => array_merge([
                'productCode' => $this->productCode,
                'remark' => $this->remark ?? '',
                'callbackUrl' => config('pakailink.callback.base_url').'/retail',
            ], $this->additionalInfo),
        ];
    }

    protected function generateReferenceNo(): string
    {
        return \Illuminate\Support\Str::random(40);
    }
}
