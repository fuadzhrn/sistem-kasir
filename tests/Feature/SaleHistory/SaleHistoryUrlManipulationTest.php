<?php

namespace Tests\Feature\SaleHistory;

class SaleHistoryUrlManipulationTest extends SaleHistoryTestCase
{
    public function test_admin_gets_not_found_for_other_branch_detail_and_receipt(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $adminA = $this->createUser('admin', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $saleB = $this->createSale($branchB, $cashierB, 'BBB-20260724-0001');

        $this->actingAs($adminA)->get(route('sales.show', $saleB))->assertNotFound();
        $this->actingAs($adminA)->get(route('sales.receipt.show', $saleB))->assertNotFound();
    }

    public function test_cashier_gets_not_found_for_another_cashiers_sale_and_owner_can_open_it(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashierA = $this->createUser('cashier', $branch);
        $cashierB = $this->createUser('cashier', $branch);
        $saleB = $this->createSale($branch, $cashierB, 'AAA-20260724-0002');

        $this->actingAs($cashierA)->get(route('sales.show', $saleB))->assertNotFound();
        $this->actingAs($cashierA)->get(route('sales.receipt.show', $saleB))->assertNotFound();
        $this->actingAs($owner)->get(route('sales.show', $saleB))->assertOk();
        $this->actingAs($owner)->get(route('sales.receipt.show', $saleB))->assertOk();
    }
}
