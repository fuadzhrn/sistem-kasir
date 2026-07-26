<?php

namespace Tests\Feature\Report;

class ReportBranchIsolationTest extends ReportTestCase
{
    public function test_owner_has_branch_filter_while_admin_is_locked_to_own_branch(): void
    {
        $branchA = $this->createBranch('RBA', ['name' => 'Cabang Laporan A']);
        $branchB = $this->createBranch('RBB', ['name' => 'Cabang Laporan B']);
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $this->createSale($branchA, $cashierA, ['invoice_number' => 'INV-CABANG-A']);
        $this->createSale($branchB, $cashierB, ['invoice_number' => 'INV-CABANG-B']);

        $this->getReport($owner, 'receipts')
            ->assertOk()
            ->assertSee('name="branch_id"', false)
            ->assertSee('INV-CABANG-A')
            ->assertSee('INV-CABANG-B');

        $this->getReport($admin, 'receipts')
            ->assertOk()
            ->assertDontSee('name="branch_id"', false)
            ->assertSee('INV-CABANG-A')
            ->assertDontSee('INV-CABANG-B');

        $this->getReport($admin, 'receipts', ['branch_id' => $branchB->id])
            ->assertRedirect()
            ->assertSessionHasErrors('branch_id');
    }

    public function test_admin_print_is_scoped_to_own_branch(): void
    {
        $branchA = $this->createBranch('RPA');
        $branchB = $this->createBranch('RPB');
        $admin = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $this->createSale($branchA, $cashierA, ['invoice_number' => 'PRINT-A']);
        $this->createSale($branchB, $cashierB, ['invoice_number' => 'PRINT-B']);

        $this->getPrintReport($admin, 'receipts')
            ->assertOk()
            ->assertSee('PRINT-A')
            ->assertDontSee('PRINT-B');
    }
}
