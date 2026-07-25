<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;
use Illuminate\Support\Facades\Gate;

class ExpenseAuthorizationTest extends ExpenseTestCase
{
    public function test_guest_cashier_and_inactive_users_cannot_access_expenses(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $inactiveAdmin = $this->createUser('admin', $branch, ['is_active' => false]);

        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('expenses.index'))->assertForbidden();
        $this->assertTrue(Gate::forUser($inactiveAdmin)->denies('viewAny', Expense::class));
    }

    public function test_admin_cannot_open_or_mutate_another_branch_expense(): void
    {
        $branchA = $this->createBranch('EAA');
        $branchB = $this->createBranch('EAB');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $expense = $this->createExpense($branchB, $owner, $category);

        $this->actingAs($admin)->get(route('expenses.show', $expense))->assertNotFound();
        $this->actingAs($admin)->get(route('expenses.edit', $expense))->assertNotFound();
        $this->actingAs($admin)->put(route('expenses.update', $expense), $this->payload($branchA, $category))
            ->assertForbidden();
        $this->assertSame('125000.00', $expense->fresh()->amount);
    }

    public function test_admin_cannot_approve_or_reject_even_own_branch_expense(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $expense = $this->createExpense($branch, $admin);

        $this->actingAs($admin)->patch(route('expenses.approve', $expense))->assertForbidden();
        $this->actingAs($admin)->patch(route('expenses.reject', $expense), [
            'rejection_reason' => 'Tidak sesuai kebutuhan toko.',
        ])->assertForbidden();
        $this->assertSame('pending', $expense->fresh()->status);
    }
}
