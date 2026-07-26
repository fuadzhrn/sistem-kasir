<?php

namespace Tests\Feature\CashierDashboard;

class CashierDashboardDataSecurityTest extends CashierDashboardTestCase
{
    public function test_cashier_markup_excludes_internal_financial_data(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier);

        $content = $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertOk()
            ->getContent();

        foreach ([
            'total_cost',
            'gross_profit',
            'cost_price',
            'average_cost',
            'purchase_price',
            'checkout_token',
            'Harga modal',
            'Pengeluaran',
            'Laba Kotor',
            'Laba Bersih',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $content);
        }
    }

    public function test_cashier_cannot_submit_scope_identifiers(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);

        foreach (['branch_id', 'cashier_id', 'user_id'] as $parameter) {
            $this->actingAs($cashier)
                ->get(route('dashboard.cashier', [$parameter => 999]))
                ->assertSessionHasErrors($parameter);
        }
    }
}
