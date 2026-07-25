<?php

namespace Tests\Feature\Expense;

use App\Services\Expense\ApprovedExpenseSummaryService;

class ExpenseSummaryTest extends ExpenseTestCase
{
    public function test_only_approved_expenses_contribute_to_summary(): void
    {
        $branchA = $this->createBranch('ESA');
        $branchB = $this->createBranch('ESB');
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $this->createExpense($branchA, $owner, $category, [
            'amount' => '100000.00',
            'status' => 'approved',
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);
        $this->createExpense($branchA, $owner, $category, ['amount' => '900000.00']);
        $this->createExpense($branchB, $owner, $category, [
            'amount' => '300000.00',
            'status' => 'approved',
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);

        $summary = app(ApprovedExpenseSummaryService::class);
        $this->assertSame('100000.00', $summary->totalForPeriod($branchA, now()->startOfDay(), now()->endOfDay()));
        $this->assertSame('400000.00', $summary->totalForPeriod(null, now()->startOfDay(), now()->endOfDay()));
        $grossProfit = 1_000_000;
        $approvedExpense = 150_000;
        $this->assertSame(850_000, $grossProfit - $approvedExpense);
    }

    public function test_index_summary_respects_admin_branch_scope(): void
    {
        $branchA = $this->createBranch('SMA');
        $branchB = $this->createBranch('SMB');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $this->createExpense($branchA, $admin, null, ['amount' => '110000.00']);
        $this->createExpense($branchB, $owner, null, ['amount' => '990000.00']);

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Rp110.000')
            ->assertDontSee('Rp990.000');
    }
}
