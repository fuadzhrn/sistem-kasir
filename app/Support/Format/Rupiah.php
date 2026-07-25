<?php

namespace App\Support\Format;

final class Rupiah
{
    public static function format(string|int $value): string
    {
        return self::formatMinor(self::toMinor((string) $value));
    }

    public static function difference(string|int $oldValue, string|int $newValue): int
    {
        return self::toMinor((string) $newValue) - self::toMinor((string) $oldValue);
    }

    public static function formatMinor(int $minorValue): string
    {
        $absolute = abs($minorValue);
        $whole = intdiv($absolute, 100);
        $fraction = $absolute % 100;
        $formatted = 'Rp'.number_format($whole, 0, ',', '.');

        if ($fraction !== 0) {
            $formatted .= ','.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
        }

        return $minorValue < 0 ? "-{$formatted}" : $formatted;
    }

    private static function toMinor(string $value): int
    {
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim(trim($value), '+-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);
        $minor = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$minor : $minor;
    }
}
