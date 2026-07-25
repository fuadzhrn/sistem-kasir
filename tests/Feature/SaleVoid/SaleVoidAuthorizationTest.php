<?php

namespace Tests\Feature\SaleVoid;

class SaleVoidAuthorizationTest extends SaleVoidTestCase
{
    public function test_owner_all_branches_admin_own_branch_and_cashier_own_sale(): void
    {
        $branchA = $this->createBranch('VAA');
        $branchB = $this->createBranch('VAB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $otherCashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        ['sale' => $saleA] = $this->createVoidableSale($branchA, $cashierA);
        ['sale' => $saleB] = $this->createVoidableSale($branchB, $cashierB);

        $this->actingAs($otherCashierA)->patch(route('sales.void', $saleA), $this->voidPayload())
            ->assertForbidden();
        $this->actingAs($adminA)->patch(route('sales.void', $saleB), $this->voidPayload())
            ->assertForbidden();
        $this->actingAs($adminA)->patch(route('sales.void', $saleA), $this->voidPayload())
            ->assertRedirect();
        $this->actingAs($owner)->patch(route('sales.void', $saleB), $this->voidPayload())
            ->assertRedirect();
    }

    public function test_guest_is_redirected_and_inactive_user_is_denied_by_policy(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);
        $inactiveOwner = $this->createUser('owner', null, ['is_active' => false]);

        $this->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertRedirect(route('login'));
        $this->assertFalse($inactiveOwner->can('void', $sale));
    }
}
