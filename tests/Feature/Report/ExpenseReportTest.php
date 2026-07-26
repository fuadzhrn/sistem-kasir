<?php

namespace Tests\Feature\Report;

use App\Models\Expense;

class ExpenseReportTest extends ReportTestCase
{
    public function test_expense_report_filters_status_and_totals_all_filtered_rows(): void
    {
        $branch = $this->createBranch('REX');
        $owner = $this->createUser('owner');
        $approved = $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, [
            'description' => 'Biaya laporan disetujui',
            'amount' => '25000.00',
        ]);
        $this->createExpense($branch, $owner, Expense::STATUS_PENDING, [
            'description' => 'Biaya laporan pending',
            'amount' => '15000.00',
        ]);

        $this->getReport($owner, 'expenses', ['status' => 'approved'])
            ->assertOk()
            ->assertSee($approved->description)
            ->assertDontSee('Biaya laporan pending')
            ->assertSee('Rp25.000');
        $this->getReport($owner, 'expenses', ['search' => 'pending'])
            ->assertOk()
            ->assertSee('Biaya laporan pending');
        $this->getPrintReport($owner, 'expenses', ['status' => 'approved'])->assertOk();
    }
}
