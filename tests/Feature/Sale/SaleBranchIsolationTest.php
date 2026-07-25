<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;

class SaleBranchIsolationTest extends SaleTestCase
{
    public function test_owner_can_checkout_each_selected_active_branch(): void
    {
        $branchA = $this->createBranch('BRA');
        $branchB = $this->createBranch('BRB');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branchA, $product, '5.000', '10000.00');
        $this->createStock($branchB, $product, '8.000', '15000.00');
        $payment = $this->createPaymentMethod();

        foreach ([$branchA, $branchB] as $branch) {
            $this->actingAs($owner)
                ->postJson(
                    route('cashier.checkout.store'),
                    $this->payload($owner, $branch, $product, $payment),
                )
                ->assertCreated();
        }

        $this->assertSame(
            [$branchA->id, $branchB->id],
            Sale::query()->orderBy('id')->pluck('branch_id')->all(),
        );
        $this->assertSame(
            ['20000.00', '30000.00'],
            Sale::query()->orderBy('id')->pluck('total_cost')->all(),
        );
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'quantity' => 6,
        ]);
    }

    public function test_admin_and_cashier_cannot_override_account_branch(): void
    {
        foreach (['admin', 'cashier'] as $index => $role) {
            $branchA = $this->createBranch('A'.$index);
            $branchB = $this->createBranch('B'.$index);
            $user = $this->createUser($role, $branchA);
            $product = $this->createProduct();
            $stockA = $this->createStock($branchA, $product);
            $stockB = $this->createStock($branchB, $product);
            $payment = $this->createPaymentMethod(['code' => 'PAY'.$index]);
            $payload = $this->payload($user, $branchA, $product, $payment, [
                'branch_id' => $branchB->id,
            ]);

            $this->actingAs($user)
                ->postJson(route('cashier.checkout.store'), $payload)
                ->assertForbidden()
                ->assertJsonPath('code', 'BRANCH_NOT_ALLOWED');
            $this->assertSame('10.000', $stockA->refresh()->quantity);
            $this->assertSame('10.000', $stockB->refresh()->quantity);
        }

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_inactive_account_branch_is_rejected_without_stock_change(): void
    {
        $branch = $this->createBranch('INA');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        $branch->update(['is_active' => false]);

        $this->actingAs($cashier)
            ->postJson(
                route('cashier.checkout.store'),
                $this->payload($cashier, $branch, $product, $payment),
            )
            ->assertForbidden()
            ->assertJsonPath('code', 'BRANCH_NOT_ALLOWED');

        $this->assertSame('10.000', $stock->refresh()->quantity);
    }
}
