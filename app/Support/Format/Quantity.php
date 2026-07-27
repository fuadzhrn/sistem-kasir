<?php

namespace App\Support\Format;

final class Quantity
{
    public static function format(string|int|float|null $value): string
    {
        $normalized = self::normalizeInput($value);

        if (! is_string($normalized) || ! preg_match('/\A(?<sign>-?)(?<whole>\d+)(?:\.(?<fraction>\d{1,3}))?\z/', $normalized, $matches)) {
            return '0';
        }

        $whole = ltrim($matches['whole'], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($matches['fraction'] ?? '', '0');
        $sign = $matches['sign'] === '-' && ($whole !== '0' || $fraction !== '') ? '-' : '';
        $formatted = $sign.self::groupThousands($whole);

        return $fraction === '' ? $formatted : $formatted.','.$fraction;
    }

    public static function signed(string|int|float|null $value): string
    {
        $normalized = self::normalizeInput($value);
        $formatted = self::format($normalized);

        return is_string($normalized)
            && ! str_starts_with($normalized, '-')
            && self::isNonZero($normalized)
                ? '+'.$formatted
                : $formatted;
    }

    public static function inputValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $normalized = self::normalizeInput($value);

        if (is_string($normalized) && preg_match('/\A-?\d+(?:\.\d{1,3})?\z/', $normalized)) {
            [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
            $fraction = rtrim($fraction, '0');

            return $fraction === '' ? $whole : $whole.','.$fraction;
        }

        return is_scalar($value) ? (string) $value : '';
    }

    public static function normalizeInput(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                return $value;
            }

            return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (str_contains($trimmed, ',')) {
            if (! preg_match('/\A-?(?:\d+|\d{1,3}(?:\.\d{3})+),\d{1,3}\z/', $trimmed)) {
                return $trimmed;
            }

            return str_replace(',', '.', str_replace('.', '', $trimmed));
        }

        if (! preg_match('/\A-?\d+(?:\.\d{1,3})?\z/', $trimmed)) {
            return $trimmed;
        }

        return $trimmed;
    }

    private static function groupThousands(string $whole): string
    {
        $length = strlen($whole);
        $firstGroupLength = $length % 3 ?: 3;
        $groups = [substr($whole, 0, $firstGroupLength)];

        for ($offset = $firstGroupLength; $offset < $length; $offset += 3) {
            $groups[] = substr($whole, $offset, 3);
        }

        return implode('.', $groups);
    }

    private static function isNonZero(string $value): bool
    {
        return preg_replace('/[-.0]/', '', $value) !== '';
    }
}
