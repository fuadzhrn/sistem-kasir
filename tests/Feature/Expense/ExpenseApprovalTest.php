<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;

class ExpenseApprovalTest extends ExpenseTestCase
{
    public function test_owner_can_approve_pending_expense_with_reviewer_metadata(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $admin);

        $this->actingAs($owner)->patch(route('expenses.approve', $expense))
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();
        $this->assertSame(Expense::STATUS_APPROVED, $expense->status);
        $this->assertSame($owner->id, $expense->approved_by);
        $this->assertNotNull($expense->approved_at);
        $this->assertNull($expense->rejected_by);
    }

    public function test_approved_expense_cannot_be_approved_twice(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $owner);

        $this->actingAs($owner)->patch(route('expenses.approve', $expense))->assertRedirect();
        $this->actingAs($owner)->patch(route('expenses.approve', $expense))->assertForbidden();
        $this->assertDatabaseCount('expenses', 1);
    }
}
