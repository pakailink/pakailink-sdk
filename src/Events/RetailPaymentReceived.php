<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\RetailCallbackData;

class RetailPaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RetailCallbackData $data,
        public array $rawPayload,
    ) {}
}
