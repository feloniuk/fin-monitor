<?php

namespace App\Helpers;

class CurrencyHelper
{
    private static array $symbols = [
        980 => '₴',
        840 => '$',
        978 => '€',
        826 => '£',
        985 => 'zł',
    ];

    private static array $codes = [
        980 => 'UAH',
        840 => 'USD',
        978 => 'EUR',
        826 => 'GBP',
        985 => 'PLN',
    ];

    public static function symbol(int $isoCode): string
    {
        return self::$symbols[$isoCode] ?? (string) $isoCode;
    }

    public static function code(int $isoCode): string
    {
        return self::$codes[$isoCode] ?? (string) $isoCode;
    }

    public static function format(int $amountInCents, int $currencyCode): string
    {
        $amount = $amountInCents / 100;
        $symbol = self::symbol($currencyCode);

        return number_format($amount, 2, '.', ' ') . ' ' . $symbol;
    }
}
