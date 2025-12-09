<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\EmoneyCallbackData;

class EmoneyPaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EmoneyCallbackData $data,
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

    public function getChannelId(): string
    {
        return $this->data->channelId;
    }

    public function getChannelName(): string
    {
        return $this->data->getChannelName();
    }

    public function isSuccess(): bool
    {
        return $this->data->isSuccess();
    }
}
