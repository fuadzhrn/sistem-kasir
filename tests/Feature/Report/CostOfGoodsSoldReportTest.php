<?php

namespace Tests\Feature\Report;

class CostOfGoodsSoldReportTest extends ReportTestCase
{
    public function test_cost_report_uses_immutable_item_cost_snapshot(): void
    {
        $branch = $this->createBranch('RCO');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct('RCO-001', ['purchase_price' => '999999.00']);
        $this->createSale(
            $branch,
            $cashier,
            ['invoice_number' => 'HPP-SNAPSHOT'],
            $product,
            ['quantity' => '2.000', 'cost_price' => '95000.00', 'profit' => '-10000.00'],
        );

        $this->getReport($owner, 'cost-of-goods-sold')
            ->assertOk()
            ->assertSee('HPP-SNAPSHOT')
            ->assertSee('Rp95.000')
            ->assertSee('Rp190.000')
            ->assertDontSee('Rp999.999');
        $this->getPrintReport($owner, 'cost-of-goods-sold')
            ->assertOk()
            ->assertSee('Rp190.000');
    }
}
