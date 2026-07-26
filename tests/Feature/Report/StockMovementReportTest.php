<?php

namespace Tests\Feature\Report;

class StockMovementReportTest extends ReportTestCase
{
    public function test_stock_movement_report_handles_null_reference_and_protects_unit_cost(): void
    {
        $branch = $this->createBranch('RSM');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct('MOVE-001');
        $this->createStockMovement($branch, $admin, $product);

        $this->getReport($owner, 'stock-movements')
            ->assertOk()
            ->assertSee('MOVE-001')
            ->assertSee('Unit Cost')
            ->assertSee('Rp60.000');
        $this->getReport($admin, 'stock-movements', ['search' => 'MOVE-001'])
            ->assertOk()
            ->assertSee('MOVE-001')
            ->assertDontSee('Unit Cost')
            ->assertDontSee('Rp60.000');
        $this->getPrintReport($admin, 'stock-movements')->assertOk()->assertDontSee('Unit Cost');
    }
}
