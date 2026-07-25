<?php

namespace Tests\Feature\Expense;

class ExpenseCategoryDeletionTest extends ExpenseTestCase
{
    public function test_only_owner_can_delete_an_unused_category(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $forAdmin = $this->createCategory();

        $this->actingAs($admin)->delete(route('expense-categories.destroy', $forAdmin))
            ->assertForbidden();
        $this->actingAs($owner)->delete(route('expense-categories.destroy', $forAdmin))
            ->assertRedirect(route('expense-categories.index'));
        $this->assertDatabaseMissing('expense_categories', ['id' => $forAdmin->id]);
    }

    public function test_used_category_cannot_be_deleted_and_history_is_preserved(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $expense = $this->createExpense($branch, $owner, $category);

        $this->actingAs($owner)->delete(route('expense-categories.destroy', $category))
            ->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }
}
