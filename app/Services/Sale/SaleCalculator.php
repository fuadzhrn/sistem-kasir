<?php

namespace App\Services\Sale;

use App\Services\Calculation\WeightedAverageCostCalculator;
use InvalidArgumentException;

class SaleCalculator
{
    public function __construct(
        private readonly WeightedAverageCostCalculator $decimalCalculator,
    ) {}

    public function normalizeMoney(string $value): string
    {
        return $this->decimalCalculator->normalizeMoney($value);
    }

    public function normalizeQuantity(string $value): string
    {
        return $this->decimalCalculator->normalizeQuantity($value);
    }

    public function calculateLineSubtotal(string $quantity, string $sellingPrice): string
    {
        return $this->decimalCalculator->multiplyQuantityByPrice($quantity, $sellingPrice);
    }

    public function calculateLineCost(string $quantity, string $costPrice): string
    {
        return $this->decimalCalculator->multiplyQuantityByPrice($quantity, $costPrice);
    }

    /**
     * @param  array<int, string>  $values
     */
    public function sumMoney(array $values): string
    {
        $total = 0;

        foreach ($values as $value) {
            $amount = $this->moneyToCents($value);

            if (($amount > 0 && $total > PHP_INT_MAX - $amount)
                || ($amount < 0 && $total < PHP_INT_MIN - $amount)) {
                throw new InvalidArgumentException('Hasil perhitungan uang terlalu besar.');
            }

            $total += $amount;
        }

        return $this->centsToMoney($total);
    }

    public function subtractMoney(string $amount, string $deduction): string
    {
        return $this->centsToMoney(
            $this->moneyToCents($amount) - $this->moneyToCents($deduction),
        );
    }

    public function calculateTotal(string $subtotal, string $discount): string
    {
        $subtotalCents = $this->moneyToCents($subtotal);
        $discountCents = $this->moneyToCents($discount);

        if ($discountCents < 0 || $discountCents > $subtotalCents) {
            throw new InvalidArgumentException('Diskon tidak boleh melebihi subtotal.');
        }

        return $this->centsToMoney($subtotalCents - $discountCents);
    }

    public function calculateChange(string $amountPaid, string $total): string
    {
        $change = $this->moneyToCents($amountPaid) - $this->moneyToCents($total);

        if ($change < 0) {
            throw new InvalidArgumentException('Pembayaran tidak mencukupi.');
        }

        return $this->centsToMoney($change);
    }

    public function calculateProfit(string $netSubtotal, string $cost): string
    {
        return $this->subtractMoney($netSubtotal, $cost);
    }

    public function compareMoney(string $left, string $right): int
    {
        return $this->moneyToCents($left) <=> $this->moneyToCents($right);
    }

    public function compareQuantity(string $left, string $right): int
    {
        return $this->quantityToMills($left) <=> $this->quantityToMills($right);
    }

    public function subtractQuantity(string $left, string $right): string
    {
        return $this->millsToQuantity(
            $this->quantityToMills($left) - $this->quantityToMills($right),
        );
    }

    public function negateQuantity(string $value): string
    {
        return $this->millsToQuantity(-$this->quantityToMills($value));
    }

    public function moneyToCents(string $value): int
    {
        return $this->parseScaled($value, 2, 'Nilai uang');
    }

    public function centsToMoney(int $value): string
    {
        return $this->formatScaled($value, 2);
    }

    public function quantityToMills(string $value): int
    {
        return $this->parseScaled($value, 3, 'Quantity');
    }

    public function millsToQuantity(int $value): string
    {
        return $this->formatScaled($value, 3);
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
        $digits = ltrim($whole.str_pad($fraction, $scale, '0'), '0') ?: '0';

        $maximum = (string) PHP_INT_MAX;

        if (
            strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)
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
}
