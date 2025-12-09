<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\VirtualAccountCallbackData;

class VirtualAccountPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public VirtualAccountCallbackData $data,
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

    public function getVirtualAccountNo(): string
    {
        return $this->data->virtualAccountNo;
    }

    public function getBankCode(): ?string
    {
        return $this->data->getBankCode();
    }

    public function isSuccess(): bool
    {
        return $this->data->isSuccess();
    }
}
