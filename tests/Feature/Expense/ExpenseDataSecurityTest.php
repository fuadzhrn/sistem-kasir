<?php

namespace Tests\Feature\Expense;

class ExpenseDataSecurityTest extends ExpenseTestCase
{
    public function test_description_and_rejection_reason_are_escaped_in_html(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $owner, null, [
            'description' => '<script>alert("expense")</script>',
            'status' => 'rejected',
            'rejected_by' => $owner->id,
            'rejected_at' => now(),
            'rejection_reason' => '<img src=x onerror=alert(1)>',
        ]);

        $this->actingAs($owner)->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertDontSee('<script>alert("expense")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;alert(&quot;expense&quot;)&lt;/script&gt;', false);
    }

    public function test_expense_pages_do_not_expose_storage_paths_or_credentials(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $expense = $this->createExpense($branch, $owner);

        $this->actingAs($owner)->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertDontSee('DB_PASSWORD')
            ->assertDontSee('APP_KEY=')
            ->assertDontSee(base_path(), false);
    }
}
