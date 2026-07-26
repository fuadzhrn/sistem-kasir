<?php

namespace Tests\Feature\Report;

class CashierReportTest extends ReportTestCase
{
    public function test_cashier_report_aggregates_receipts_and_respects_branch_scope(): void
    {
        $branchA = $this->createBranch('RCA', ['name' => 'Cabang Kasir A']);
        $branchB = $this->createBranch('RCB', ['name' => 'Cabang Kasir B']);
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA, ['name' => 'Kasir Laporan A']);
        $cashierB = $this->createUser('cashier', $branchB, ['name' => 'Kasir Laporan B']);
        $this->createSale($branchA, $cashierA);
        $this->createSale($branchB, $cashierB);

        $this->getReport($owner, 'cashiers')
            ->assertOk()
            ->assertSee('Kasir Laporan A')
            ->assertSee('Kasir Laporan B')
            ->assertSee('Rp180.000');
        $this->getReport($admin, 'cashiers', ['search' => 'Kasir'])
            ->assertOk()
            ->assertSee('Kasir Laporan A')
            ->assertDontSee('Kasir Laporan B');
        $this->getPrintReport($admin, 'cashiers')->assertOk()->assertDontSee('Kasir Laporan B');
    }
}
