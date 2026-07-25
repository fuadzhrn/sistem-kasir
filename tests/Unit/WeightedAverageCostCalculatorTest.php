<?php

namespace Tests\Unit;

use App\Services\Calculation\WeightedAverageCostCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WeightedAverageCostCalculatorTest extends TestCase
{
    private WeightedAverageCostCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new WeightedAverageCostCalculator;
    }

    #[DataProvider('weightedAverageCases')]
    public function test_weighted_average_is_precise_and_rounded_half_up(
        string $oldQuantity,
        string $oldCost,
        string $incomingQuantity,
        string $incomingPrice,
        string $expected,
    ): void {
        $this->assertSame(
            $expected,
            $this->calculator->calculateWeightedAverage(
                $oldQuantity,
                $oldCost,
                $incomingQuantity,
                $incomingPrice,
            ),
        );
    }

    public static function weightedAverageCases(): array
    {
        return [
            'stok lama nol' => ['0', '0', '10', '25000', '25000.00'],
            'contoh stok positif' => ['10', '50000', '5', '60000', '53333.33'],
            'quantity pecahan tiga desimal' => ['2.500', '20000', '1.250', '24000', '21333.33'],
            'quantity masuk kecil' => ['100', '10000', '1', '20000', '10099.01'],
            'harga masuk lebih rendah' => ['10', '50000', '10', '40000', '45000.00'],
            'quantity masuk besar' => ['1', '10000', '999999.999', '20000', '19999.99'],
            'pembulatan half-up' => ['1', '1.00', '1', '1.01', '1.01'],
        ];
    }

    public function test_quantity_and_money_normalization_do_not_use_float_results(): void
    {
        $this->assertSame('1.230', $this->calculator->normalizeQuantity('1,23'));
        $this->assertSame('10000.50', $this->calculator->normalizeMoney('10000,5'));
        $this->assertSame('50000.00', $this->calculator->calculateSubtotal('2.500', '20000'));
        $this->assertSame('0.30', $this->calculator->calculateSubtotal('3', '0.10'));
    }

    #[DataProvider('invalidInputCases')]
    public function test_zero_negative_and_invalid_precision_are_rejected(
        string $quantity,
        string $price,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateWeightedAverage('0', '0', $quantity, $price);
    }

    public static function invalidInputCases(): array
    {
        return [
            'quantity nol' => ['0', '10000'],
            'quantity negatif' => ['-1', '10000'],
            'harga nol' => ['1', '0'],
            'harga negatif' => ['1', '-10000'],
            'quantity terlalu presisi' => ['1.0001', '10000'],
            'harga terlalu presisi' => ['1', '10000.001'],
        ];
    }
}
