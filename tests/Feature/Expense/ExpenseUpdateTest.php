<?php

namespace Tests\Feature\Expense;

class ExpenseUpdateTest extends ExpenseTestCase
{
    public function test_pending_expense_can_be_updated_without_changing_branch(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $category = $this->createCategory();
        $newCategory = $this->createCategory(['name' => 'Kendaraan']);
        $expense = $this->createExpense($branch, $admin, $category);
        $payload = $this->payload($branch, $newCategory, [
            'branch_id' => null,
            'amount' => '275.000',
            'description' => 'Perawatan kendaraan operasional cabang',
        ]);

        unset($payload['branch_id']);
        $this->actingAs($admin)->put(route('expenses.update', $expense), $payload)
            ->assertRedirect(route('expenses.show', $expense));

        $expense->refresh();
        $this->assertSame($branch->id, $expense->branch_id);
        $this->assertSame($newCategory->id, $expense->expense_category_id);
        $this->assertSame('275000.00', $expense->amount);
        $this->assertSame($admin->id, $expense->updated_by);
    }
}
