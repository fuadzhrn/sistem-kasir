<?php

namespace Tests\Feature\Cashier;

class CashierBranchIsolationTest extends CashierTestCase
{
    public function test_owner_selects_each_branch_and_receives_its_own_stock(): void
    {
        $branchA = $this->createBranch('BRA');
        $branchB = $this->createBranch('BRB');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['code' => 'GLOBAL']);
        $this->createStock($branchA, $product, '4.000');
        $this->createStock($branchB, $product, '19.000');

        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branchA->id,
        ]))->assertOk()->assertJsonPath('data.0.stock_quantity', '4.000');
        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branchB->id,
        ]))->assertOk()->assertJsonPath('data.0.stock_quantity', '19.000');
    }

    public function test_admin_and_cashier_cannot_send_another_branch_id(): void
    {
        $branchA = $this->createBranch('BRA');
        $branchB = $this->createBranch('BRB');
        $product = $this->createProduct();
        $this->createStock($branchA, $product, '4.000');
        $this->createStock($branchB, $product, '99.000');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);

        foreach ([$admin, $cashier] as $user) {
            $this->actingAs($user)->getJson(route('cashier.products.index', [
                'branch_id' => $branchB->id,
            ]))->assertUnprocessable();

            $this->actingAs($user)->getJson(route('cashier.products.index'))
                ->assertOk()
                ->assertJsonPath('data.0.stock_quantity', '4.000')
                ->assertJsonMissing(['stock_quantity' => '99.000']);
        }
    }

    public function test_page_query_branch_spoof_is_rejected_for_admin_and_cashier(): void
    {
        $branchA = $this->createBranch('BRA');
        $branchB = $this->createBranch('BRB');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);

        foreach ([$admin, $cashier] as $user) {
            $this->actingAs($user)->get(route('cashier.index', ['branch_id' => $branchB->id]))
                ->assertSessionHasErrors('branch_id');
        }
    }

    public function test_owner_cannot_select_inactive_or_unknown_branch(): void
    {
        $inactive = $this->createBranch('OFF', ['is_active' => false]);
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->get(route('cashier.index', ['branch_id' => $inactive->id]))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => 999999,
        ]))->assertUnprocessable();
    }
}
