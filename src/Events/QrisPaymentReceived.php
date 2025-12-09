<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\QrisCallbackData;

class QrisPaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QrisCallbackData $data,
        public array $rawPayload,
    ) {}

    public function getPartnerReferenceNo(): string
    {
        return $this->data->originalPartnerReferenceNo;
    }

    public function getAmount(): float
    {
        return $this->data->getAmount();
    }

    public function getReferenceNo(): string
    {
        return $this->data->originalReferenceNo;
    }

    public function isSuccess(): bool
    {
        return $this->data->isSuccess();
    }
}
