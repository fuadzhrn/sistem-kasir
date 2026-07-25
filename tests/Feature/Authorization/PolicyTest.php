<?php

namespace Tests\Feature\Authorization;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PolicyTest extends AuthorizationTestCase
{
    public function test_branch_and_product_policies_follow_role_rules(): void
    {
        $branchA = $this->createBranch('POA');
        $branchB = $this->createBranch('POB');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);
        $product = Product::factory()->create(['is_active' => true]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $branchB));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $branchA));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $branchB));
        $this->assertFalse(Gate::forUser($cashier)->allows('update', $branchA));

        $this->assertTrue(Gate::forUser($owner)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
        $this->assertFalse(Gate::forUser($cashier)->allows('view', $product));
        $this->assertTrue(Gate::forUser($cashier)->allows('viewForSale', $product));
        $this->assertFalse(Gate::forUser($cashier)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($cashier)->allows('update', $product));
    }

    public function test_branch_stock_sale_and_expense_policies_isolate_branch(): void
    {
        $branchA = $this->createBranch('PSA');
        $branchB = $this->createBranch('PSB');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $otherCashierA = $this->createUser('cashier', $branchA);
        $product = Product::factory()->create();
        $stockA = BranchStock::query()->create([
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'quantity' => '5.000',
            'average_cost' => '10000.00',
        ]);
        $saleA = $this->createSale($branchA, $cashierA, 'INV-POL-001');
        $otherSaleA = $this->createSale($branchA, $otherCashierA, 'INV-POL-002');
        $saleB = $this->createSale($branchB, $this->createUser('cashier', $branchB), 'INV-POL-003');
        $expenseA = $this->createExpense($branchA, $admin, 'Pengeluaran A');

        $this->assertTrue(Gate::forUser($cashierA)->allows('view', $stockA));
        $this->assertFalse(Gate::forUser($cashierA)->allows('adjust', $stockA));
        $this->assertTrue(Gate::forUser($admin)->allows('adjust', $stockA));

        $this->assertTrue(Gate::forUser($owner)->allows('view', $saleB));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $saleA));
        $this->assertTrue(Gate::forUser($cashierA)->allows('view', $saleA));
        $this->assertFalse(Gate::forUser($cashierA)->allows('view', $otherSaleA));
        $this->assertFalse(Gate::forUser($cashierA)->allows('approveVoid', $saleA));

        $this->assertTrue(Gate::forUser($owner)->allows('view', $expenseA));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $expenseA));
        $this->assertFalse(Gate::forUser($cashierA)->allows('view', $expenseA));
        $this->assertFalse(Gate::forUser($cashierA)->allows('viewAny', Expense::class));
    }

    public function test_user_policy_hides_owner_from_admin_and_denies_cashier_module_access(): void
    {
        $branch = $this->createBranch('USR');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $admin));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $owner));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $cashier));
        $this->assertFalse(Gate::forUser($cashier)->allows('view', $cashier));
        $this->assertFalse(Gate::forUser($cashier)->allows('view', $admin));
        $this->assertFalse(Gate::forUser($cashier)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($owner)->allows('viewAny', Sale::class));
        $this->assertTrue(Gate::forUser($owner)->allows('viewAny', Branch::class));
    }
}
