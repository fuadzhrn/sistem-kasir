<?php

namespace App\Services\Calculation;

use InvalidArgumentException;

class WeightedAverageCostCalculator
{
    public function normalizeMoney(string $value): string
    {
        return $this->formatScaled($this->parseScaled($value, 2, 'Nilai uang'), 2);
    }

    public function normalizeQuantity(string $value): string
    {
        return $this->formatScaled($this->parseScaled($value, 3, 'Quantity'), 3);
    }

    public function multiplyQuantityByPrice(string $quantity, string $price): string
    {
        $quantityUnits = $this->parseScaled($quantity, 3, 'Quantity');
        $priceCents = $this->parseScaled($price, 2, 'Harga');

        if ($quantityUnits < 0 || $priceCents < 0) {
            throw new InvalidArgumentException('Quantity dan harga tidak boleh negatif.');
        }

        $numerator = $this->multiplyUnsigned((string) $quantityUnits, (string) $priceCents);
        $subtotalCents = $this->divideRoundHalfUp($numerator, 1000);

        return $this->formatScaled($subtotalCents, 2);
    }

    public function calculateWeightedAverage(
        string $oldQuantity,
        string $oldAverageCost,
        string $incomingQuantity,
        string $incomingPrice,
    ): string {
        $oldQuantityUnits = $this->parseScaled($oldQuantity, 3, 'Quantity lama');
        $oldCostCents = $this->parseScaled($oldAverageCost, 2, 'Average cost lama');
        $incomingQuantityUnits = $this->parseScaled($incomingQuantity, 3, 'Quantity masuk');
        $incomingPriceCents = $this->parseScaled($incomingPrice, 2, 'Harga beli');

        if ($oldQuantityUnits < 0 || $oldCostCents < 0) {
            throw new InvalidArgumentException('Stok dan average cost lama tidak boleh negatif.');
        }

        if ($incomingQuantityUnits <= 0 || $incomingPriceCents <= 0) {
            throw new InvalidArgumentException('Quantity dan harga beli harus lebih besar dari nol.');
        }

        if ($oldQuantityUnits === 0) {
            return $this->formatScaled($incomingPriceCents, 2);
        }

        $newQuantityUnits = $oldQuantityUnits + $incomingQuantityUnits;
        $oldValue = $this->multiplyUnsigned((string) $oldQuantityUnits, (string) $oldCostCents);
        $incomingValue = $this->multiplyUnsigned((string) $incomingQuantityUnits, (string) $incomingPriceCents);
        $newValue = $this->addUnsigned($oldValue, $incomingValue);
        $averageCents = $this->divideRoundHalfUp($newValue, $newQuantityUnits);

        return $this->formatScaled($averageCents, 2);
    }

    public function calculateSubtotal(string $quantity, string $purchasePrice): string
    {
        $quantityUnits = $this->parseScaled($quantity, 3, 'Quantity');
        $priceCents = $this->parseScaled($purchasePrice, 2, 'Harga beli');

        if ($quantityUnits <= 0 || $priceCents <= 0) {
            throw new InvalidArgumentException('Quantity dan harga beli harus lebih besar dari nol.');
        }

        return $this->multiplyQuantityByPrice($quantity, $purchasePrice);
    }

    public function addMoney(string $left, string $right): string
    {
        $sum = $this->parseScaled($left, 2, 'Nilai uang')
            + $this->parseScaled($right, 2, 'Nilai uang');

        return $this->formatScaled($sum, 2);
    }

    public function addQuantity(string $left, string $right): string
    {
        $sum = $this->parseScaled($left, 3, 'Quantity')
            + $this->parseScaled($right, 3, 'Quantity');

        return $this->formatScaled($sum, 3);
    }

    private function parseScaled(string $value, int $scale, string $label): int
    {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^-?\d+(?:\.\d{1,'.$scale.'})?$/', $normalized)) {
            throw new InvalidArgumentException("{$label} tidak valid.");
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $fraction = str_pad($fraction, $scale, '0');
        $digits = ltrim($whole.$fraction, '0');
        $digits = $digits === '' ? '0' : $digits;

        if (
            strlen($digits) > 18
            || (strlen($digits) === 18 && strcmp($digits, (string) PHP_INT_MAX) > 0)
        ) {
            throw new InvalidArgumentException("{$label} terlalu besar.");
        }

        $result = (int) $digits;

        return $negative ? -$result : $result;
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $digits = str_pad((string) abs($value), $scale + 1, '0', STR_PAD_LEFT);

        return $sign.substr($digits, 0, -$scale).'.'.substr($digits, -$scale);
    }

    private function multiplyUnsigned(string $left, string $right): string
    {
        $result = array_fill(0, strlen($left) + strlen($right), 0);

        for ($leftIndex = strlen($left) - 1; $leftIndex >= 0; $leftIndex--) {
            for ($rightIndex = strlen($right) - 1; $rightIndex >= 0; $rightIndex--) {
                $position = $leftIndex + $rightIndex + 1;
                $product = ((int) $left[$leftIndex] * (int) $right[$rightIndex]) + $result[$position];
                $result[$position] = $product % 10;
                $result[$position - 1] += intdiv($product, 10);
            }
        }

        return ltrim(implode('', $result), '0') ?: '0';
    }

    private function addUnsigned(string $left, string $right): string
    {
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $carry = 0;
        $result = '';

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $sum = ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0)
                + $carry;
            $result = ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return $result;
    }

    private function divideRoundHalfUp(string $numerator, int $denominator): int
    {
        if ($denominator <= 0) {
            throw new InvalidArgumentException('Pembagi harus lebih besar dari nol.');
        }

        $quotient = '';
        $remainder = 0;

        for ($index = 0; $index < strlen($numerator); $index++) {
            $remainder = ($remainder * 10) + (int) $numerator[$index];
            $quotient .= (string) intdiv($remainder, $denominator);
            $remainder %= $denominator;
        }

        $quotient = ltrim($quotient, '0') ?: '0';

        if ($remainder * 2 >= $denominator) {
            $quotient = $this->addUnsigned($quotient, '1');
        }

        if (
            strlen($quotient) > 18
            || (strlen($quotient) === 18 && strcmp($quotient, (string) PHP_INT_MAX) > 0)
        ) {
            throw new InvalidArgumentException('Hasil perhitungan terlalu besar.');
        }

        return (int) $quotient;
    }
}
