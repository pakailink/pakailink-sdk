<?php

namespace PakaiLink\Enums;

enum PakaiLinkTransactionType: string
{
    case VIRTUAL_ACCOUNT = 'virtual_account';
    case QRIS = 'qris';
    case EWALLET = 'ewallet';
    case RETAIL = 'retail';
    case TRANSFER_BANK = 'transfer_bank';
    case TRANSFER_VA = 'transfer_va';
    case TOP_UP = 'top_up';
    case BALANCE_INQUIRY = 'balance_inquiry';

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
            self::VIRTUAL_ACCOUNT => 'Virtual Account',
            self::QRIS => 'QRIS Payment',
            self::EWALLET => 'E-Wallet Payment',
            self::RETAIL => 'Retail Payment',
            self::TRANSFER_BANK => 'Bank Transfer',
            self::TRANSFER_VA => 'Transfer to VA',
            self::TOP_UP => 'Top Up',
            self::BALANCE_INQUIRY => 'Balance Inquiry',
        };
    }

    public function isDeposit(): bool
    {
        return in_array($this, [
            self::VIRTUAL_ACCOUNT,
            self::QRIS,
            self::EWALLET,
            self::RETAIL,
            self::TOP_UP,
        ]);
    }

    public function isWithdrawal(): bool
    {
        return in_array($this, [
            self::TRANSFER_BANK,
            self::TRANSFER_VA,
        ]);
    }
}
