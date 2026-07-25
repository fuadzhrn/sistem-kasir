<?php

namespace Tests\Feature\Receipt;

class ReceiptPrintAuthorizationTest extends ReceiptPrintTestCase
{
    public function test_owner_admin_and_cashier_can_only_print_their_scope(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $adminB = $this->createUser('admin', $branchB);
        $cashierA1 = $this->createUser('cashier', $branchA);
        $cashierA2 = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $saleA1 = $this->createSale($branchA, $cashierA1, 'AAA-20260724-0001');
        $saleA2 = $this->createSale($branchA, $cashierA2, 'AAA-20260724-0002');
        $saleB = $this->createSale($branchB, $cashierB, 'BBB-20260724-0001');

        foreach ([$saleA1, $saleA2, $saleB] as $sale) {
            $this->actingAs($owner)->get($this->printUrl($sale->id))->assertOk();
        }

        $this->actingAs($adminA)->get($this->printUrl($saleA1->id))->assertOk();
        $this->actingAs($adminA)->get($this->printUrl($saleB->id))->assertNotFound();
        $this->actingAs($adminB)->get($this->printUrl($saleA1->id))->assertNotFound();
        $this->actingAs($cashierA1)->get($this->printUrl($saleA1->id))->assertOk();
        $this->actingAs($cashierA1)->get($this->printUrl($saleA2->id))->assertNotFound();
        $this->actingAs($cashierA1)->get($this->printUrl($saleB->id))->assertNotFound();
        $this->actingAs($cashierB)->get($this->printUrl($saleA1->id))->assertNotFound();
    }

    public function test_guest_inactive_user_and_url_query_manipulation_are_safe(): void
    {
        $branch = $this->createBranch('AAA');
        $cashier = $this->createUser('cashier', $branch);
        $otherCashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $this->get($this->printUrl($sale->id))->assertRedirect(route('login'));

        $cashier->update(['is_active' => false]);
        $this->actingAs($cashier)->get($this->printUrl($sale->id))->assertRedirect(route('login'));

        $cashier->update(['is_active' => true]);
        $branch->update(['is_active' => false]);
        $this->actingAs($cashier)->get($this->printUrl($sale->id))->assertOk();

        $this->actingAs($otherCashier)->get($this->printUrl($sale->id, [
            'copy' => 1,
            'paper' => '80',
            'cashier_id' => $cashier->id,
        ]))->assertNotFound();
    }
}
