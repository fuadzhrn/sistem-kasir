<?php

namespace Tests\Feature\CashierDashboard;

class CashierDashboardUrlManipulationTest extends CashierDashboardTestCase
{
    public function test_cashier_cannot_open_or_print_another_cashiers_sale(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $otherCashier = $this->createUser('cashier', $branch);
        ['sale' => $otherSale] = $this->createSale($branch, $otherCashier);

        $this->actingAs($cashier)
            ->get(route('sales.show', $otherSale))
            ->assertNotFound();
        $this->actingAs($cashier)
            ->get(route('receipts.print', $otherSale))
            ->assertNotFound();
    }
}
