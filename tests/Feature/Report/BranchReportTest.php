<?php

namespace Tests\Feature\Report;

class BranchReportTest extends ReportTestCase
{
    public function test_branch_report_compares_financial_results_without_cross_branch_leak(): void
    {
        $branchA = $this->createBranch('RBR-A', ['name' => 'Cabang Kinerja A']);
        $branchB = $this->createBranch('RBR-B', ['name' => 'Cabang Kinerja B']);
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $this->createSale($branchA, $cashierA);
        $this->createSale($branchB, $cashierB);
        $this->createExpense($branchA, $owner);

        $this->getReport($owner, 'branches')
            ->assertOk()
            ->assertSee('Cabang Kinerja A')
            ->assertSee('Cabang Kinerja B');
        $this->getReport($admin, 'branches')
            ->assertOk()
            ->assertSee('Cabang Kinerja A')
            ->assertDontSee('Cabang Kinerja B');
        $this->getPrintReport($admin, 'branches')->assertOk()->assertDontSee('Cabang Kinerja B');
    }
}
