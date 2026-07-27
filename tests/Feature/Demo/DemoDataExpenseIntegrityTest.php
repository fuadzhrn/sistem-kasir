<?php

namespace Tests\Feature\Demo;

use App\Models\Expense;

class DemoDataExpenseIntegrityTest extends DemoDataTestCase
{
    public function test_expense_workflow_contains_all_required_states_and_metadata(): void
    {
        $this->seedDemo();

        $this->assertTrue(Expense::query()->where('status', Expense::STATUS_PENDING)->exists());
        $this->assertTrue(Expense::query()->where('status', Expense::STATUS_APPROVED)->whereNotNull('approved_by')->exists());
        $this->assertTrue(
            Expense::query()
                ->where('status', Expense::STATUS_REJECTED)
                ->whereNotNull('rejected_by')
                ->whereNotNull('rejection_reason')
                ->exists(),
        );
    }
}
