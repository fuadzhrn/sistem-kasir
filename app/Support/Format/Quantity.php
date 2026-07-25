<?php

namespace App\Support\Format;

final class Quantity
{
    public static function format(string|int|float|null $value): string
    {
        $normalized = number_format((float) ($value ?? 0), 3, '.', '');
        $normalized = rtrim(rtrim($normalized, '0'), '.');

        return str_replace('.', ',', $normalized === '-0' ? '0' : $normalized);
    }

    public static function signed(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);
        $formatted = self::format($number);

        return $number > 0 ? '+'.$formatted : $formatted;
    }
}
