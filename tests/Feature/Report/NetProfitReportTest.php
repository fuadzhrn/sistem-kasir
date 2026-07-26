<?php

namespace Tests\Feature\Report;

use App\Models\Expense;

class NetProfitReportTest extends ReportTestCase
{
    public function test_net_profit_uses_only_approved_expenses_and_locked_formula(): void
    {
        $branch = $this->createBranch('RNP');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, [
            'total' => '280000.00',
            'total_cost' => '190000.00',
            'gross_profit' => '90000.00',
        ]);
        $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, ['amount' => '25000.00']);
        $this->createExpense($branch, $owner, Expense::STATUS_PENDING, ['amount' => '99000.00']);
        $this->createExpense($branch, $owner, Expense::STATUS_REJECTED, ['amount' => '88000.00']);

        $this->getReport($owner, 'net-profit', ['granularity' => 'daily'])
            ->assertOk()
            ->assertSee('Rp280.000')
            ->assertSee('Rp190.000')
            ->assertSee('Rp90.000')
            ->assertSee('Rp25.000')
            ->assertSee('Rp65.000')
            ->assertDontSee('Rp99.000')
            ->assertDontSee('Rp88.000');
        $this->getPrintReport($owner, 'net-profit', ['granularity' => 'daily'])
            ->assertOk()
            ->assertSee('Rp65.000');
    }
}
