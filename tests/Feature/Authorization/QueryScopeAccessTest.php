<?php

namespace Tests\Feature\Authorization;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\User;

class QueryScopeAccessTest extends AuthorizationTestCase
{
    public function test_branch_sale_expense_and_user_scopes_apply_role_rules_in_sql(): void
    {
        $branchA = $this->createBranch('SCA', 'Cabang A');
        $branchB = $this->createBranch('SCB', 'Cabang B');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $adminB = $this->createUser('admin', $branchB);
        $cashierA = $this->createUser('cashier', $branchA);
        $otherCashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $adminWithoutBranch = $this->createUser('admin');

        $saleA = $this->createSale($branchA, $cashierA, 'INV-SCP-001');
        $otherSaleA = $this->createSale($branchA, $otherCashierA, 'INV-SCP-002');
        $saleB = $this->createSale($branchB, $cashierB, 'INV-SCP-003');
        $expenseA = $this->createExpense($branchA, $adminA, 'Scope A');
        $expenseB = $this->createExpense($branchB, $adminB, 'Scope B');

        $this->assertEqualsCanonicalizing([$branchA->id, $branchB->id], Branch::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$branchA->id], Branch::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([$branchA->id], Branch::accessibleTo($cashierA)->pluck('id')->all());
        $this->assertSame([], Branch::accessibleTo($adminWithoutBranch)->pluck('id')->all());

        $this->assertEqualsCanonicalizing([$saleA->id, $otherSaleA->id, $saleB->id], Sale::accessibleTo($owner)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$saleA->id, $otherSaleA->id], Sale::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([$saleA->id], Sale::accessibleTo($cashierA)->pluck('id')->all());
        $this->assertNotContains($otherSaleA->id, Sale::accessibleTo($cashierA)->pluck('id')->all());
        $this->assertNotContains($saleB->id, Sale::accessibleTo($cashierA)->pluck('id')->all());

        $this->assertEqualsCanonicalizing([$expenseA->id, $expenseB->id], Expense::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$expenseA->id], Expense::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([], Expense::accessibleTo($cashierA)->pluck('id')->all());

        $this->assertCount(7, User::accessibleTo($owner)->get());
        $this->assertEqualsCanonicalizing(
            [$adminA->id, $cashierA->id, $otherCashierA->id],
            User::accessibleTo($adminA)->pluck('id')->all(),
        );
        $this->assertNotContains($owner->id, User::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([$cashierA->id], User::accessibleTo($cashierA)->pluck('id')->all());
    }

    public function test_other_branch_models_use_explicit_branch_scope(): void
    {
        $branchA = $this->createBranch('MBA');
        $branchB = $this->createBranch('MBB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $cashierA = $this->createUser('cashier', $branchA);
        $product = Product::factory()->create();

        $stockA = BranchStock::query()->create([
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
            'average_cost' => '1000.00',
        ]);
        $stockB = BranchStock::query()->create([
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'quantity' => '2.000',
            'average_cost' => '1000.00',
        ]);
        $receiptA = StockReceipt::query()->create([
            'branch_id' => $branchA->id,
            'receipt_number' => 'RCV-SCP-A',
            'receipt_date' => now()->toDateString(),
            'total_cost' => '1000.00',
            'created_by' => $adminA->id,
        ]);
        $receiptB = StockReceipt::query()->create([
            'branch_id' => $branchB->id,
            'receipt_number' => 'RCV-SCP-B',
            'receipt_date' => now()->toDateString(),
            'total_cost' => '1000.00',
            'created_by' => $owner->id,
        ]);
        $movementA = StockMovement::query()->create([
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'created_by' => $adminA->id,
            'movement_type' => StockMovement::TYPE_INITIAL,
            'quantity_before' => '0.000',
            'quantity_change' => '1.000',
            'quantity_after' => '1.000',
            'unit_cost' => '1000.00',
        ]);
        $movementB = StockMovement::query()->create([
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'created_by' => $owner->id,
            'movement_type' => StockMovement::TYPE_INITIAL,
            'quantity_before' => '0.000',
            'quantity_change' => '2.000',
            'quantity_after' => '2.000',
            'unit_cost' => '1000.00',
        ]);
        $logA = ActivityLog::query()->create([
            'user_id' => $adminA->id,
            'branch_id' => $branchA->id,
            'action' => 'viewed',
            'module' => 'authorization-test',
            'description' => 'Log A',
        ]);
        $logB = ActivityLog::query()->create([
            'user_id' => $owner->id,
            'branch_id' => $branchB->id,
            'action' => 'viewed',
            'module' => 'authorization-test',
            'description' => 'Log B',
        ]);

        $this->assertEqualsCanonicalizing([$stockA->id, $stockB->id], BranchStock::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$stockA->id], BranchStock::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([$stockA->id], BranchStock::accessibleTo($cashierA)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$receiptA->id, $receiptB->id], StockReceipt::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$receiptA->id], StockReceipt::accessibleTo($adminA)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$movementA->id, $movementB->id], StockMovement::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$movementA->id], StockMovement::accessibleTo($adminA)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$logA->id, $logB->id], ActivityLog::accessibleTo($owner)->pluck('id')->all());
        $this->assertSame([$logA->id], ActivityLog::accessibleTo($adminA)->pluck('id')->all());
        $this->assertSame([], ActivityLog::accessibleTo($cashierA)->pluck('id')->all());
    }
}
