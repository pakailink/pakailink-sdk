<?php

namespace PakaiLink\Enums;

enum PakaiLinkBankCode: string
{
    // Major Banks
    case BRI = '002';
    case MANDIRI = '008';
    case BNI = '009';
    case DANAMON = '011';
    case PERMATA = '013';
    case BCA = '014';
    case MAYBANK = '016';
    case PANIN = '019';
    case CIMB = '022';
    case OCBC = '028';
    case BTN = '200';

    // Syariah Banks
    case MUAMALAT = '147';
    case BSI = '451';
    case BCA_SYARIAH = '536';

    // Digital Banks
    case NEO = '490';
    case JAGO = '542';
    case SEABANK = '535';

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
            self::BRI => 'Bank BRI',
            self::MANDIRI => 'Bank Mandiri',
            self::BNI => 'Bank BNI',
            self::DANAMON => 'Bank Danamon',
            self::PERMATA => 'Bank Permata',
            self::BCA => 'Bank BCA',
            self::MAYBANK => 'Maybank',
            self::PANIN => 'Bank Panin',
            self::CIMB => 'Bank CIMB Niaga',
            self::OCBC => 'Bank OCBC NISP',
            self::BTN => 'Bank BTN',
            self::MUAMALAT => 'Bank Muamalat',
            self::BSI => 'Bank Syariah Indonesia',
            self::BCA_SYARIAH => 'Bank BCA Syariah',
            self::NEO => 'Bank Neo',
            self::JAGO => 'Bank Jago',
            self::SEABANK => 'SeaBank Indonesia',
        };
    }

    public function isSyariah(): bool
    {
        return in_array($this, [
            self::MUAMALAT,
            self::BSI,
            self::BCA_SYARIAH,
        ]);
    }

    public function isDigital(): bool
    {
        return in_array($this, [
            self::NEO,
            self::JAGO,
            self::SEABANK,
        ]);
    }

    public static function popular(): array
    {
        return [
            self::BRI,
            self::MANDIRI,
            self::BNI,
            self::BCA,
            self::CIMB,
            self::PERMATA,
        ];
    }

    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
