<?php

namespace Tests\Feature\SaleHistory;

class SaleHistoryAuthorizationTest extends SaleHistoryTestCase
{
    public function test_owner_admin_and_cashier_receive_only_their_allowed_sales(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $cashierA1 = $this->createUser('cashier', $branchA);
        $cashierA2 = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $this->createSale($branchA, $cashierA1, 'AAA-20260724-0001');
        $this->createSale($branchA, $cashierA2, 'AAA-20260724-0002');
        $this->createSale($branchB, $cashierB, 'BBB-20260724-0001');

        $this->actingAs($owner)->get(route('sales.index'))
            ->assertSee('AAA-20260724-0001')->assertSee('AAA-20260724-0002')->assertSee('BBB-20260724-0001');
        $this->actingAs($adminA)->get(route('sales.index'))
            ->assertSee('AAA-20260724-0001')->assertSee('AAA-20260724-0002')->assertDontSee('BBB-20260724-0001');
        $this->actingAs($cashierA1)->get(route('sales.index'))
            ->assertSee('AAA-20260724-0001')->assertDontSee('AAA-20260724-0002')->assertDontSee('BBB-20260724-0001');
    }

    public function test_admin_and_cashier_cannot_expand_access_with_filters(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $adminA = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $this->createSale($branchB, $cashierB, 'BBB-20260724-0001');

        $this->actingAs($adminA)->get(route('sales.index', ['branch_id' => $branchB->id]))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($cashierA)->get(route('sales.index', ['cashier_id' => $cashierB->id]))
            ->assertSessionHasErrors('cashier_id');
    }
}
