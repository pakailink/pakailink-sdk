<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\TransferCallbackData;

class TransferCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TransferCallbackData $data,
        public array $rawPayload,
    ) {}

    public function getPartnerReferenceNo(): string
    {
        return $this->data->partnerReferenceNo;
    }

    public function getAmount(): float
    {
        return $this->data->getAmount();
    }

    public function getBeneficiaryBankCode(): string
    {
        return $this->data->beneficiaryBankCode;
    }

    public function getBeneficiaryAccountNo(): string
    {
        return $this->data->beneficiaryAccountNo;
    }

    public function isSuccess(): bool
    {
        return $this->data->isSuccess();
    }
}
