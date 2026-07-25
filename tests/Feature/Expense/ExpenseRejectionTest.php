<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;

class ExpenseRejectionTest extends ExpenseTestCase
{
    public function test_owner_can_reject_with_mandatory_reason_and_metadata(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $admin);

        $this->actingAs($owner)->patch(route('expenses.reject', $expense), [
            'rejection_reason' => 'Nota tidak sesuai dengan pengajuan.',
        ])->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();
        $this->assertSame(Expense::STATUS_REJECTED, $expense->status);
        $this->assertSame($owner->id, $expense->rejected_by);
        $this->assertNotNull($expense->rejected_at);
        $this->assertSame('Nota tidak sesuai dengan pengajuan.', $expense->rejection_reason);
        $this->assertNull($expense->approved_by);
    }

    public function test_rejection_requires_a_meaningful_reason(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $owner);

        $this->actingAs($owner)->patch(route('expenses.reject', $expense), [
            'rejection_reason' => 'Pendek',
        ])->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending', $expense->fresh()->status);
    }
}
