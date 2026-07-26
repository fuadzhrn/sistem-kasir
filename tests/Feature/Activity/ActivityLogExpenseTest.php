<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use Tests\Feature\Expense\ExpenseTestCase;

class ActivityLogExpenseTest extends ExpenseTestCase
{
    public function test_expense_creation_is_audited_for_its_branch(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $category = $this->createCategory();

        $payload = $this->payload($branch, $category);
        unset($payload['branch_id']);

        $this->actingAs($admin)
            ->post(route('expenses.store'), $payload)
            ->assertRedirect();

        $this->assertSame(1, ActivityLog::query()->where('action', 'expense_created')->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'expense_created',
            'branch_id' => $branch->id,
        ]);
    }
}
