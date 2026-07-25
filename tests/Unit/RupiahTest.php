<?php

namespace Tests\Unit;

use App\Support\Format\Rupiah;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RupiahTest extends TestCase
{
    #[DataProvider('formattedValues')]
    public function test_money_is_formatted_as_indonesian_rupiah_without_decimals(
        string|int $value,
        string $expected,
    ): void {
        $this->assertSame($expected, Rupiah::format($value));
    }

    /**
     * @return array<string, array{string|int, string}>
     */
    public static function formattedValues(): array
    {
        return [
            'zero' => ['0.00', 'Rp0'],
            'ten thousand' => ['10000.00', 'Rp10.000'],
            'seventy five thousand' => ['75000.00', 'Rp75.000'],
            'one million two hundred fifty thousand' => [1250000, 'Rp1.250.000'],
            'fraction below half rounds down' => ['10000.49', 'Rp10.000'],
            'fraction from half rounds up' => ['10000.50', 'Rp10.001'],
            'negative' => ['-1250.00', '-Rp1.250'],
        ];
    }

    public function test_formatted_and_canonical_inputs_are_normalized_before_validation(): void
    {
        $this->assertSame('10000', Rupiah::normalizeInput('10.000'));
        $this->assertSame('1250000', Rupiah::normalizeInput('1.250.000'));
        $this->assertSame('10000', Rupiah::normalizeInput('10000.00'));
        $this->assertSame('-10000', Rupiah::normalizeInput('-10.000'));
        $this->assertSame('10,99', Rupiah::normalizeInput('10,99'));
        $this->assertSame('10.000', Rupiah::input('10000.00'));
    }
}
