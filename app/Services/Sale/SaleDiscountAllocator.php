<?php

namespace App\Services\Sale;

use InvalidArgumentException;

class SaleDiscountAllocator
{
    public function __construct(
        private readonly SaleCalculator $calculator,
    ) {}

    /**
     * @param  array<int, string>  $lineSubtotals
     * @return array<int, string>
     */
    public function allocate(array $lineSubtotals, string $totalDiscount): array
    {
        if ($lineSubtotals === []) {
            return [];
        }

        $lineCents = array_map(
            fn (string $subtotal): int => $this->calculator->moneyToCents($subtotal),
            $lineSubtotals,
        );
        $subtotalCents = array_sum($lineCents);
        $discountCents = $this->calculator->moneyToCents($totalDiscount);

        if ($subtotalCents < 0 || $discountCents < 0 || $discountCents > $subtotalCents) {
            throw new InvalidArgumentException('Diskon transaksi tidak valid.');
        }

        if ($discountCents === 0) {
            return array_fill(0, count($lineCents), '0.00');
        }

        $allocations = [];
        $allocated = 0;
        $lastIndex = array_key_last($lineCents);

        foreach ($lineCents as $index => $lineSubtotal) {
            if ($lineSubtotal < 0) {
                throw new InvalidArgumentException('Subtotal item tidak valid.');
            }

            $allocation = $index === $lastIndex
                ? $discountCents - $allocated
                : $this->multiplyDivideRoundHalfUp(
                    $discountCents,
                    $lineSubtotal,
                    $subtotalCents,
                );
            $allocation = min($allocation, $lineSubtotal, $discountCents - $allocated);
            $allocations[] = $this->calculator->centsToMoney($allocation);
            $allocated += $allocation;
        }

        if ($allocated !== $discountCents) {
            throw new InvalidArgumentException('Alokasi diskon tidak konsisten.');
        }

        return $allocations;
    }

    private function multiplyDivideRoundHalfUp(int $left, int $right, int $denominator): int
    {
        if ($denominator <= 0) {
            throw new InvalidArgumentException('Subtotal transaksi harus lebih besar dari nol.');
        }

        if ($left === 0 || $right === 0) {
            return 0;
        }

        $multiplier = $right;
        $resultQuotient = 0;
        $resultRemainder = 0;
        $addQuotient = intdiv($left, $denominator);
        $addRemainder = $left % $denominator;

        while ($multiplier > 0) {
            if (($multiplier % 2) === 1) {
                [$resultQuotient, $resultRemainder] = $this->addDivisionParts(
                    $resultQuotient,
                    $resultRemainder,
                    $addQuotient,
                    $addRemainder,
                    $denominator,
                );
            }

            $multiplier = intdiv($multiplier, 2);

            if ($multiplier > 0) {
                [$addQuotient, $addRemainder] = $this->addDivisionParts(
                    $addQuotient,
                    $addRemainder,
                    $addQuotient,
                    $addRemainder,
                    $denominator,
                );
            }
        }

        $roundingThreshold = intdiv($denominator, 2) + ($denominator % 2);

        return $resultQuotient + ($resultRemainder >= $roundingThreshold ? 1 : 0);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function addDivisionParts(
        int $leftQuotient,
        int $leftRemainder,
        int $rightQuotient,
        int $rightRemainder,
        int $denominator,
    ): array {
        if ($leftQuotient > PHP_INT_MAX - $rightQuotient) {
            throw new InvalidArgumentException('Nilai diskon terlalu besar untuk dialokasikan.');
        }

        $quotient = $leftQuotient + $rightQuotient;
        $distanceToDenominator = $denominator - $rightRemainder;

        if ($leftRemainder >= $distanceToDenominator) {
            if ($quotient === PHP_INT_MAX) {
                throw new InvalidArgumentException('Nilai diskon terlalu besar untuk dialokasikan.');
            }

            return [
                $quotient + 1,
                $leftRemainder - $distanceToDenominator,
            ];
        }

        return [
            $quotient,
            $leftRemainder + $rightRemainder,
        ];
    }
}
