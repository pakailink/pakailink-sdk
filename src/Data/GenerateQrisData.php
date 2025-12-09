<?php

namespace PakaiLink\Data;

use Carbon\Carbon;

class GenerateQrisData
{
    public function __construct(
        public string $merchantId,
        public float $amount,
        public ?string $partnerReferenceNo = null,
        public ?string $storeId = null,
        public ?string $terminalId = null,
        public ?Carbon $validityPeriod = null,
        public array $additionalInfo = [],
    ) {}

    public static function from(array $data): self
    {
        // Support both camelCase and snake_case keys
        $validityPeriod = $data['validityPeriod'] ?? $data['validity_period'] ?? null;

        // If validityPeriod is a string number (minutes), convert to Carbon
        if (is_string($validityPeriod) && is_numeric($validityPeriod)) {
            $validityPeriod = now()->addMinutes((int) $validityPeriod);
        } elseif ($validityPeriod) {
            $validityPeriod = Carbon::parse($validityPeriod);
        }

        return new self(
            merchantId: $data['merchantId'] ?? $data['merchant_id'],
            amount: $data['amount'],
            partnerReferenceNo: $data['partnerReferenceNo'] ?? $data['partner_reference_no'] ?? null,
            storeId: $data['storeId'] ?? $data['store_id'] ?? null,
            terminalId: $data['terminalId'] ?? $data['terminal_id'] ?? null,
            validityPeriod: $validityPeriod,
            additionalInfo: $data['additionalInfo'] ?? $data['additional_info'] ?? [],
        );
    }

    public function toApiPayload(): array
    {
        // Format validityPeriod as per SNAP API: YYYY-MM-DDTHH:mm:ss+07:00
        $validityPeriod = $this->validityPeriod
            ? $this->validityPeriod->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP')
            : now()->timezone('Asia/Jakarta')->addHour()->format('Y-m-d\TH:i:sP');

        return [
            'merchantId' => $this->merchantId,
            'storeId' => $this->storeId ?? 'PAKAILINK',
            'terminalId' => $this->terminalId ?? 'ID'.time(),
            'partnerReferenceNo' => $this->partnerReferenceNo ?? $this->generateReferenceNo(),
            'amount' => [
                'value' => number_format($this->amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'validityPeriod' => $validityPeriod,
            'additionalInfo' => array_merge([
                'callbackUrl' => config('pakailink.callback.base_url').'/qris',
            ], $this->additionalInfo),
        ];
    }

    protected function generateReferenceNo(): string
    {
        return \Illuminate\Support\Str::random(40);
    }
}
