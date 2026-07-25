<?php

namespace Tests\Feature\Expense;

use Illuminate\Support\Facades\Gate;

class ExpenseImmutabilityTest extends ExpenseTestCase
{
    public function test_approved_and_rejected_expenses_cannot_be_edited_or_deleted(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $approved = $this->createExpense($branch, $owner, $category, [
            'status' => 'approved',
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);
        $rejected = $this->createExpense($branch, $owner, $category, [
            'status' => 'rejected',
            'rejected_by' => $owner->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Tidak sesuai pengajuan.',
        ]);

        foreach ([$approved, $rejected] as $expense) {
            $this->assertFalse(Gate::forUser($owner)->allows('update', $expense));
            $this->assertFalse(Gate::forUser($owner)->allows('delete', $expense));
            $this->actingAs($owner)->get(route('expenses.edit', $expense))->assertForbidden();
            $this->actingAs($owner)->put(route('expenses.update', $expense), $this->payload($branch, $category))
                ->assertForbidden();
        }

        $this->assertFalse(app('router')->has('expenses.destroy'));
    }

    public function test_final_expense_cannot_change_decision_or_review_metadata(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $owner);

        $this->actingAs($owner)->patch(route('expenses.approve', $expense))->assertRedirect();
        $approvedAt = $expense->fresh()->approved_at;
        $this->actingAs($owner)->patch(route('expenses.reject', $expense), [
            'rejection_reason' => 'Mencoba mengganti keputusan final.',
        ])->assertForbidden();

        $expense->refresh();
        $this->assertSame('approved', $expense->status);
        $this->assertTrue($approvedAt->equalTo($expense->approved_at));
        $this->assertNull($expense->rejection_reason);
    }
}
