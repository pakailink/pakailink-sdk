<?php

namespace PakaiLink\Data;

use Carbon\Carbon;

class CreateVirtualAccountData
{
    public function __construct(
        public float $amount,
        public string $customerName,
        public string $bankCode,
        public ?string $customerNo = null,
        public ?string $virtualAccountPhone = null,
        public ?string $virtualAccountEmail = null,
        public ?Carbon $expiredDate = null,
        public ?string $partnerReferenceNo = null,
        public array $additionalInfo = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            amount: $data['amount'],
            customerName: $data['customerName'] ?? $data['customer_name'],
            bankCode: $data['bankCode'] ?? $data['bank_code'],
            customerNo: $data['customerNo'] ?? $data['customer_no'] ?? null,
            virtualAccountPhone: $data['virtualAccountPhone'] ?? $data['virtual_account_phone'] ?? null,
            virtualAccountEmail: $data['virtualAccountEmail'] ?? $data['virtual_account_email'] ?? null,
            expiredDate: isset($data['expiredDate']) || isset($data['expired_date'])
                ? Carbon::parse($data['expiredDate'] ?? $data['expired_date'])
                : null,
            partnerReferenceNo: $data['partnerReferenceNo'] ?? $data['partner_reference_no'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? $data['additional_info'] ?? [],
        );
    }

    public function toApiPayload(string $partnerServiceId): array
    {
        $partnerReferenceNo = $this->partnerReferenceNo ?? $this->generateReferenceNo();

        // Format expiredDate as per SNAP API: YYYY-MM-DDTHH:mm:ss+07:00
        $expiredDate = $this->expiredDate
            ? $this->expiredDate->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP')
            : now()->timezone('Asia/Jakarta')->addHours(24)->format('Y-m-d\TH:i:sP');

        return [
            'partnerReferenceNo' => $partnerReferenceNo,
            'customerNo' => $this->customerNo ?? substr($partnerReferenceNo, -15),
            'virtualAccountName' => $this->customerName,
            'virtualAccountPhone' => $this->virtualAccountPhone,
            'virtualAccountEmail' => $this->virtualAccountEmail,
            'expiredDate' => $expiredDate,
            'totalAmount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => array_merge([
                'callbackUrl' => config('pakailink.callback.base_url').'/va',
                'bankCode' => $this->bankCode,
            ], $this->additionalInfo),
        ];
    }

    protected function generateReferenceNo(): string
    {
        return \Illuminate\Support\Str::random(40);
    }
}
