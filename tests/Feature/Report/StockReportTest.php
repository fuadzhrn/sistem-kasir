<?php

namespace Tests\Feature\Report;

class StockReportTest extends ReportTestCase
{
    public function test_stock_report_classifies_status_and_hides_cost_from_admin(): void
    {
        $branch = $this->createBranch('RST');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $out = $this->createProduct('STOK-HABIS');
        $low = $this->createProduct('STOK-MENIPIS');
        $safe = $this->createProduct('STOK-AMAN');
        $this->createStock($branch, $out, '0.000');
        $this->createStock($branch, $low, '2.000');
        $this->createStock($branch, $safe, '10.000');

        $this->getReport($owner, 'stocks')
            ->assertOk()
            ->assertSee('Habis')
            ->assertSee('Menipis')
            ->assertSee('Aman')
            ->assertSee('Average Cost')
            ->assertSee('Nilai Persediaan');
        $this->getReport($owner, 'stocks', ['stock_status' => 'out', 'search' => 'STOK'])
            ->assertOk()
            ->assertSee('STOK-HABIS')
            ->assertDontSee('STOK-MENIPIS');
        $this->getReport($admin, 'stocks')
            ->assertOk()
            ->assertDontSee('Average Cost')
            ->assertDontSee('Nilai Persediaan');
        $this->getPrintReport($admin, 'stocks')->assertOk()->assertDontSee('Average Cost');
    }
}
