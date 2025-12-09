<?php

namespace PakaiLink\Enums;

enum PakaiLinkTransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
            self::EXPIRED => 'gray',
            self::CANCELLED => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCESS,
            self::FAILED,
            self::EXPIRED,
            self::CANCELLED,
        ]);
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
        ]);
    }
}
