<?php

namespace PakaiLink\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallbackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $type,
        public array $payload,
        public bool $isValid,
        public ?string $signature = null,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }
}
