<?php

namespace Tests\Unit;

use App\Services\Calculation\WeightedAverageCostCalculator;
use App\Services\Sale\SaleCalculator;
use App\Services\Sale\SaleDiscountAllocator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SaleDiscountAllocatorTest extends TestCase
{
    private SaleDiscountAllocator $allocator;

    private SaleCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new SaleCalculator(new WeightedAverageCostCalculator);
        $this->allocator = new SaleDiscountAllocator($this->calculator);
    }

    public function test_zero_and_single_line_discounts_are_exact(): void
    {
        $this->assertSame(['0.00'], $this->allocator->allocate(['100.00'], '0.00'));
        $this->assertSame(['25.00'], $this->allocator->allocate(['100.00'], '25.00'));
    }

    public function test_equal_and_different_subtotals_are_allocated_proportionally(): void
    {
        $this->assertSame(
            ['5.00', '5.00'],
            $this->allocator->allocate(['50.00', '50.00'], '10.00'),
        );
        $this->assertSame(
            ['2.50', '7.50'],
            $this->allocator->allocate(['25.00', '75.00'], '10.00'),
        );
    }

    public function test_last_item_receives_rounding_remainder_deterministically(): void
    {
        $expected = ['0.33', '0.33', '0.34'];

        $this->assertSame($expected, $this->allocator->allocate(
            ['1.00', '1.00', '1.00'],
            '1.00',
        ));
        $this->assertSame($expected, $this->allocator->allocate(
            ['1.00', '1.00', '1.00'],
            '1.00',
        ));
        $this->assertSame('1.00', $this->calculator->sumMoney($expected));
    }

    public function test_full_discount_never_exceeds_line_subtotal(): void
    {
        $allocations = $this->allocator->allocate(
            ['10.00', '20.00', '30.00'],
            '60.00',
        );

        $this->assertSame(['10.00', '20.00', '30.00'], $allocations);
    }

    public function test_invalid_discount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->allocator->allocate(['10.00'], '10.01');
    }

    public function test_large_decimal_values_are_allocated_without_float_or_integer_overflow(): void
    {
        $allocations = $this->allocator->allocate(
            ['9999999999999999.98', '0.01'],
            '5000000000000000.00',
        );

        $this->assertSame('5000000000000000.00', $this->calculator->sumMoney($allocations));
        $this->assertLessThanOrEqual(
            0,
            $this->calculator->compareMoney($allocations[0], '9999999999999999.98'),
        );
        $this->assertLessThanOrEqual(
            0,
            $this->calculator->compareMoney($allocations[1], '0.01'),
        );
    }
}
