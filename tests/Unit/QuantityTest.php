<?php

namespace Tests\Unit;

use App\Support\Format\Quantity;
use App\Support\Format\Rupiah;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuantityTest extends TestCase
{
    #[DataProvider('formattedValues')]
    public function test_quantity_is_formatted_without_unnecessary_decimal_zeroes(
        string $value,
        string $expected,
    ): void {
        $this->assertSame($expected, Quantity::format($value));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function formattedValues(): array
    {
        return [
            'zero' => ['0.000', '0'],
            'one' => ['1.000', '1'],
            'one and a half' => ['1.500', '1,5'],
            'one and a quarter' => ['1.250', '1,25'],
            'one eighth' => ['0.125', '0,125'],
            'grouped fractional quantity' => ['1000.500', '1.000,5'],
        ];
    }

    public function test_quantity_input_accepts_comma_or_dot_and_preserves_invalid_values_for_validation(): void
    {
        $this->assertSame('1.5', Quantity::normalizeInput('1,5'));
        $this->assertSame('1.5', Quantity::normalizeInput('1.5'));
        $this->assertSame('1000.5', Quantity::normalizeInput('1.000,5'));
        $this->assertSame('1.0001', Quantity::normalizeInput('1.0001'));
        $this->assertSame('1000,5', Quantity::inputValue('1000.500'));
    }

    public function test_quantity_formatter_does_not_change_rupiah_formatting(): void
    {
        $this->assertSame('1,5', Quantity::format('1.500'));
        $this->assertSame('Rp10.000', Rupiah::format('10000.00'));
    }
}
