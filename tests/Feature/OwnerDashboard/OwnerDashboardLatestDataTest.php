<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Expense;
use App\Models\Sale;

class OwnerDashboardLatestDataTest extends OwnerDashboardTestCase
{
    public function test_latest_transactions_include_completed_and_voided_with_safe_limited_fields(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('LTX');

        for ($index = 0; $index < 11; $index++) {
            $this->createSale($branch, $owner, [
                'transaction_date' => '2026-07-'.str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT).' 10:00:00',
                'status' => $index === 10 ? Sale::STATUS_VOIDED : Sale::STATUS_COMPLETED,
            ]);
        }

        $response = $this->getDashboardData($owner)->assertOk();
        $response->assertJsonCount(10, 'data.latest_transactions')
            ->assertJsonPath('data.latest_transactions.0.status', 'Dibatalkan')
            ->assertJsonPath('data.latest_transactions.0.branch', $branch->name);
        $this->assertStringNotContainsString('checkout_token', $response->getContent());
        $this->assertStringNotContainsString('total_cost', $response->getContent());
    }

    public function test_latest_expenses_include_all_statuses_but_cards_only_use_approved(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('LEX');
        $this->createExpense($branch, $owner, Expense::STATUS_PENDING, [
            'expense_date' => '2026-07-23',
            'amount' => '100000.00',
        ]);
        $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, [
            'expense_date' => '2026-07-24',
            'amount' => '25000.00',
        ]);
        $this->createExpense($branch, $owner, Expense::STATUS_REJECTED, [
            'expense_date' => '2026-07-25',
            'amount' => '200000.00',
        ]);

        $response = $this->getDashboardData($owner);

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data.latest_expenses')
            ->assertJsonPath('data.latest_expenses.0.status', 'Ditolak')
            ->assertJsonPath('data.latest_expenses.1.status', 'Disetujui')
            ->assertJsonPath('data.latest_expenses.2.status', 'Menunggu Persetujuan')
            ->assertJsonPath('data.cards.approved_expenses.value', '25000.00');
    }
}
