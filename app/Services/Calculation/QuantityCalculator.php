<?php

namespace App\Services\Calculation;

use InvalidArgumentException;

class QuantityCalculator
{
    public function normalize(string $value): string
    {
        return $this->format($this->parse($value));
    }

    public function add(string $left, string $right): string
    {
        return $this->format($this->parse($left) + $this->parse($right));
    }

    public function subtract(string $left, string $right): string
    {
        return $this->format($this->parse($left) - $this->parse($right));
    }

    public function negate(string $value): string
    {
        return $this->format(-$this->parse($value));
    }

    public function absolute(string $value): string
    {
        return $this->format(abs($this->parse($value)));
    }

    public function compare(string $left, string $right): int
    {
        return $this->parse($left) <=> $this->parse($right);
    }

    private function parse(string $value): int
    {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^-?\d+(?:\.\d{1,3})?$/', $normalized)) {
            throw new InvalidArgumentException('Quantity tidak valid.');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $digits = ltrim($whole.str_pad($fraction, 3, '0'), '0') ?: '0';

        if (
            strlen($digits) > 18
            || (strlen($digits) === 18 && strcmp($digits, (string) PHP_INT_MAX) > 0)
        ) {
            throw new InvalidArgumentException('Quantity terlalu besar.');
        }

        $result = (int) $digits;

        return $negative ? -$result : $result;
    }

    private function format(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $digits = str_pad((string) abs($value), 4, '0', STR_PAD_LEFT);

        return $sign.substr($digits, 0, -3).'.'.substr($digits, -3);
    }
}
