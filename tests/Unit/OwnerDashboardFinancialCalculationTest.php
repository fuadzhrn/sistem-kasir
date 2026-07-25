<?php

namespace Tests\Unit;

use App\Services\Sale\SaleCalculator;
use App\Support\Format\Rupiah;
use Tests\TestCase;

class OwnerDashboardFinancialCalculationTest extends TestCase
{
    public function test_decimal_financial_formula_and_negative_rupiah_are_exact(): void
    {
        $calculator = app(SaleCalculator::class);
        $grossProfit = $calculator->subtractMoney('12000000.00', '8000000.00');
        $netProfit = $calculator->subtractMoney($grossProfit, '4250000.00');

        $this->assertSame('4000000.00', $grossProfit);
        $this->assertSame('-250000.00', $netProfit);
        $this->assertSame('-Rp250.000', Rupiah::format($netProfit));
        $this->assertSame('Rp10.000', Rupiah::format('10000.00'));
    }
}
