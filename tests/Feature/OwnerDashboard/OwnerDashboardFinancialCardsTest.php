<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Expense;
use App\Models\Sale;

class OwnerDashboardFinancialCardsTest extends OwnerDashboardTestCase
{
    public function test_cards_follow_locked_financial_definitions_and_branch_filter(): void
    {
        $owner = $this->createUser('owner');
        $branchA = $this->createBranch('FCA');
        $branchB = $this->createBranch('FCB');
        $this->createSale($branchA, $owner, ['gross_profit' => '999999.00']);
        $this->createSale($branchA, $owner, [
            'status' => Sale::STATUS_VOIDED,
            'subtotal' => '900000.00',
            'total' => '800000.00',
            'total_cost' => '700000.00',
        ]);
        $this->createSale($branchB, $owner, [
            'subtotal' => '100000.00',
            'discount_amount' => '0.00',
            'total' => '100000.00',
            'total_cost' => '40000.00',
            'gross_profit' => '60000.00',
        ]);
        $this->createExpense($branchA, $owner, Expense::STATUS_APPROVED);
        $this->createExpense($branchA, $owner, Expense::STATUS_PENDING, ['amount' => '99000.00']);
        $this->createExpense($branchA, $owner, Expense::STATUS_REJECTED, ['amount' => '88000.00']);

        $this->getDashboardData($owner, ['branch_id' => $branchA->id])
            ->assertOk()
            ->assertJsonPath('data.cards.gross_sales.value', '200000.00')
            ->assertJsonPath('data.cards.net_sales.value', '180000.00')
            ->assertJsonPath('data.cards.cost_of_goods_sold.value', '120000.00')
            ->assertJsonPath('data.cards.gross_profit.value', '60000.00')
            ->assertJsonPath('data.cards.approved_expenses.value', '25000.00')
            ->assertJsonPath('data.cards.net_profit.value', '35000.00')
            ->assertJsonPath('data.cards.receipt_count.value', 1)
            ->assertJsonPath('data.cards.net_sales.formatted', 'Rp180.000');
    }

    public function test_negative_net_profit_is_not_clamped_to_zero(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('NEG');
        $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, [
            'amount' => '250000.00',
        ]);

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonPath('data.cards.gross_profit.value', '0.00')
            ->assertJsonPath('data.cards.net_profit.value', '-250000.00')
            ->assertJsonPath('data.cards.net_profit.formatted', '-Rp250.000');
    }
}
