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
        $whole = intdiv(abs($minorValue) + 50, 100);
        $formatted = 'Rp'.number_format($whole, 0, ',', '.');

        return $minorValue < 0 ? "-{$formatted}" : $formatted;
    }

    public static function input(string|int|null $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $minor = self::toMinor((string) self::normalizeInput($value));
        $formatted = number_format(intdiv(abs($minor) + 50, 100), 0, ',', '.');

        return $minor < 0 ? "-{$formatted}" : $formatted;
    }

    public static function normalizeInput(mixed $value): mixed
    {
        if (! is_string($value) && ! is_int($value)) {
            return $value;
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/\ARp\s*/iu', '', $normalized) ?? $normalized;

        if (preg_match('/\A([+-]?)(\d{1,3}(?:\.\d{3})+)\z/', $normalized, $matches) === 1) {
            return $matches[1].str_replace('.', '', $matches[2]);
        }

        if (preg_match('/\A([+-]?\d+)\.0{1,2}\z/', $normalized, $matches) === 1) {
            return $matches[1];
        }

        return $normalized;
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
