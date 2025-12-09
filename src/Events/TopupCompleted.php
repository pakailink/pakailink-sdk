<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PakaiLink\Data\Callbacks\TopupCallbackData;

class TopupCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TopupCallbackData $data,
        public array $rawPayload,
    ) {}
}
