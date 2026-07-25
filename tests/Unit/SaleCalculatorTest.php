<?php

namespace Tests\Unit;

use App\Services\Calculation\WeightedAverageCostCalculator;
use App\Services\Sale\SaleCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SaleCalculatorTest extends TestCase
{
    private SaleCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new SaleCalculator(new WeightedAverageCostCalculator);
    }

    public function test_line_subtotal_supports_integer_and_three_decimal_quantity(): void
    {
        $this->assertSame('40000.00', $this->calculator->calculateLineSubtotal('2', '20000.00'));
        $this->assertSame('50000.00', $this->calculator->calculateLineSubtotal('2.500', '20000.00'));
        $this->assertSame('0.01', $this->calculator->calculateLineSubtotal('0.001', '5.00'));
    }

    public function test_money_can_be_summed_and_total_uses_transaction_discount(): void
    {
        $subtotal = $this->calculator->sumMoney(['10000.10', '20000.20', '0.70']);

        $this->assertSame('30001.00', $subtotal);
        $this->assertSame('30001.00', $this->calculator->calculateTotal($subtotal, '0.00'));
        $this->assertSame('25000.50', $this->calculator->calculateTotal($subtotal, '5000.50'));
        $this->assertSame('0.00', $this->calculator->calculateTotal($subtotal, $subtotal));
    }

    public function test_discount_larger_than_subtotal_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateTotal('10000.00', '10000.01');
    }

    public function test_cost_profit_payment_and_change_are_exact(): void
    {
        $costs = [
            $this->calculator->calculateLineCost('1.500', '12500.00'),
            $this->calculator->calculateLineCost('2.000', '5000.00'),
        ];

        $this->assertSame(['18750.00', '10000.00'], $costs);
        $this->assertSame('28750.00', $this->calculator->sumMoney($costs));
        $this->assertSame('11250.00', $this->calculator->calculateProfit('40000.00', '28750.00'));
        $this->assertSame('-3750.00', $this->calculator->calculateProfit('25000.00', '28750.00'));
        $this->assertSame('25000.00', $this->calculator->calculateChange('200000.00', '175000.00'));
    }

    public function test_insufficient_payment_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculateChange('9999.99', '10000.00');
    }

    public function test_half_up_and_scaled_parsing_do_not_depend_on_binary_float(): void
    {
        $this->assertSame('0.01', $this->calculator->calculateLineSubtotal('0.001', '5.00'));
        $this->assertSame('0.00', $this->calculator->calculateLineSubtotal('0.001', '4.99'));
        $this->assertSame(0, $this->calculator->compareMoney('0.10', '0.1'));
        $this->assertSame('0.300', $this->calculator->normalizeQuantity('0.3'));
    }
}
