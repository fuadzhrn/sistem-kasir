<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardBranchIsolationTest extends AdminDashboardTestCase
{
    public function test_admin_json_contains_only_its_branch_data(): void
    {
        $branchA = $this->createBranch('BRA', ['name' => 'Cabang Aman']);
        $branchB = $this->createBranch('BRB', ['name' => 'Cabang Rahasia']);
        $adminA = $this->createUser('admin', $branchA);
        $adminB = $this->createUser('admin', $branchB, ['name' => 'Admin Rahasia']);
        $cashierA = $this->createUser('cashier', $branchA, ['name' => 'Kasir Aman']);
        $cashierB = $this->createUser('cashier', $branchB, ['name' => 'Kasir Rahasia']);
        $this->createSale($branchA, $cashierA, ['total' => '110000.00']);
        $this->createSale($branchB, $cashierB, ['total' => '990000.00']);
        $this->createExpense($branchA, $adminA);
        $this->createExpense($branchB, $adminB, attributes: [
            'description' => 'Pengeluaran Rahasia',
        ]);

        $this->getAdminData($adminA)
            ->assertOk()
            ->assertJsonPath('data.filters.branch_name', 'Cabang Aman')
            ->assertJsonMissing(['branch_id' => $branchB->id])
            ->assertDontSee('Cabang Rahasia')
            ->assertDontSee('Kasir Rahasia')
            ->assertDontSee('Pengeluaran Rahasia');
    }

    public function test_admin_cannot_override_branch_with_query_string(): void
    {
        $branchA = $this->createBranch('BRA');
        $branchB = $this->createBranch('BRB');
        $admin = $this->createUser('admin', $branchA);

        $this->getAdminData($admin, ['branch_id' => $branchB->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
    }
}
