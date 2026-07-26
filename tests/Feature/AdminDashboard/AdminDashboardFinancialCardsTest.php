<?php

namespace Tests\Feature\AdminDashboard;

use App\Models\Expense;
use App\Models\Sale;

class AdminDashboardFinancialCardsTest extends AdminDashboardTestCase
{
    public function test_admin_financial_cards_use_completed_sales_and_approved_expenses(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, [
            'subtotal' => '300000.00',
            'discount_amount' => '20000.00',
            'total' => '280000.00',
            'total_cost' => '190000.00',
            'gross_profit' => '90000.00',
        ]);
        $this->createSale($branch, $cashier, [
            'subtotal' => '900000.00',
            'total' => '900000.00',
            'status' => Sale::STATUS_VOIDED,
        ]);
        $this->createExpense($branch, $admin, Expense::STATUS_APPROVED, [
            'amount' => '25000.00',
        ]);
        $this->createExpense($branch, $admin, Expense::STATUS_PENDING, [
            'amount' => '15000.00',
        ]);
        $this->createExpense($branch, $admin, Expense::STATUS_REJECTED, [
            'amount' => '30000.00',
        ]);

        $response = $this->getAdminData($admin)->assertOk();
        $response->assertJsonPath('data.cards.gross_sales.formatted', 'Rp300.000');
        $response->assertJsonPath('data.cards.net_sales.formatted', 'Rp280.000');
        $response->assertJsonPath('data.cards.cost_of_goods_sold.formatted', 'Rp190.000');
        $response->assertJsonPath('data.cards.gross_profit.formatted', 'Rp90.000');
        $response->assertJsonPath('data.cards.approved_expenses.formatted', 'Rp25.000');
        $response->assertJsonPath('data.cards.net_profit.formatted', 'Rp65.000');
        $response->assertJsonPath('data.cards.receipt_count.value', 1);
    }

    public function test_admin_cards_preserve_negative_net_profit(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $this->createExpense($branch, $admin, attributes: ['amount' => '25000.00']);

        $this->getAdminData($admin)
            ->assertJsonPath('data.cards.net_profit.formatted', '-Rp25.000');
    }
}
