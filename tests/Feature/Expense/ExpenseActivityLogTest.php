<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;

class ExpenseActivityLogTest extends ExpenseTestCase
{
    public function test_create_update_approve_and_reject_actions_are_logged(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();

        $this->actingAs($owner)->post(route('expenses.store'), $this->payload($branch, $category));
        $created = Expense::query()->latest('id')->firstOrFail();
        $update = $this->payload($branch, $category, ['amount' => '175.000']);
        unset($update['branch_id']);
        $this->actingAs($owner)->put(route('expenses.update', $created), $update);
        $this->actingAs($owner)->patch(route('expenses.approve', $created));

        $rejected = $this->createExpense($branch, $owner, $category);
        $this->actingAs($owner)->patch(route('expenses.reject', $rejected), [
            'rejection_reason' => 'Dokumen pengajuan tidak lengkap.',
        ]);

        foreach (['expense_created', 'expense_updated', 'expense_approved', 'expense_rejected'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'action' => $action,
                'module' => 'expenses',
                'user_id' => $owner->id,
                'branch_id' => $branch->id,
            ]);
        }
    }

    public function test_category_mutations_are_logged_without_branch_secret_data(): void
    {
        $owner = $this->createUser('owner');
        $this->actingAs($owner)->post(route('expense-categories.store'), [
            'name' => 'Keperluan Kantor',
            'description' => 'Perlengkapan',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'expense_category_created',
            'module' => 'expenses',
            'branch_id' => null,
        ]);
    }
}
