<?php

namespace PakaiLink\Data;

class TransferToBankData
{
    public function __construct(
        public string $beneficiaryBankCode,
        public string $beneficiaryAccountNumber,
        public float $amount,
        public ?string $partnerReferenceNo = null,
        public ?string $sessionId = null,
        public ?string $remark = null,
        public array $additionalInfo = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            beneficiaryBankCode: $data['beneficiaryBankCode'] ?? $data['beneficiary_bank_code'],
            beneficiaryAccountNumber: $data['beneficiaryAccountNumber'] ?? $data['beneficiary_account_number'],
            amount: $data['amount'],
            partnerReferenceNo: $data['partnerReferenceNo'] ?? $data['partner_reference_no'] ?? null,
            sessionId: $data['sessionId'] ?? $data['session_id'] ?? null,
            remark: $data['remark'] ?? null,
            additionalInfo: $data['additionalInfo'] ?? $data['additional_info'] ?? [],
        );
    }

    public function toApiPayload(): array
    {
        return [
            'partnerReferenceNo' => $this->partnerReferenceNo ?? $this->generateReferenceNo(),
            'beneficiaryAccountNumber' => $this->beneficiaryAccountNumber,
            'beneficiaryBankCode' => $this->beneficiaryBankCode,
            'sessionId' => $this->sessionId ?? 'INQ'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'amount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => array_merge([
                'callbackUrl' => config('pakailink.callback.base_url').'/transfer',
                'remark' => $this->remark ?? '',
            ], $this->additionalInfo),
        ];
    }

    public function toInquiryPayload(): array
    {
        return [
            'partnerReferenceNo' => $this->partnerReferenceNo ?? $this->generateReferenceNo(),
            'beneficiaryAccountNumber' => $this->beneficiaryAccountNumber,
            'amount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'additionalInfo' => [
                'beneficiaryBankCode' => $this->beneficiaryBankCode,
            ],
        ];
    }

    protected function generateReferenceNo(): string
    {
        return \Illuminate\Support\Str::random(40);
    }
}
