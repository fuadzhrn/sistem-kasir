<?php

namespace Tests\Feature\Report;

use App\Models\Sale;

class GrossProfitReportTest extends ReportTestCase
{
    public function test_gross_profit_uses_completed_sales_and_supports_negative_value(): void
    {
        $branch = $this->createBranch('RGP');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'LABA-AKTIF',
            'total' => '280000.00',
            'total_cost' => '190000.00',
            'gross_profit' => '90000.00',
        ]);
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'LABA-VOID',
            'status' => Sale::STATUS_VOIDED,
            'total' => '999000.00',
            'total_cost' => '1000.00',
        ]);

        $this->getReport($owner, 'gross-profit')
            ->assertOk()
            ->assertSee('LABA-AKTIF')
            ->assertDontSee('LABA-VOID')
            ->assertSee('Rp280.000')
            ->assertSee('Rp190.000')
            ->assertSee('Rp90.000');
    }
}
