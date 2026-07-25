<?php

namespace Tests\Feature\SaleHistory;

class SaleHistoryDataSecurityTest extends SaleHistoryTestCase
{
    public function test_cashier_html_and_all_receipt_html_do_not_contain_cost_data_or_secrets(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'checkout_token' => 'private-checkout-token-stage-14',
            'total_cost' => '98765.43',
            'gross_profit' => '71234.57',
        ]);

        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertDontSee('98.765,43')
            ->assertDontSee('71.234,57')
            ->assertDontSee('39.506,17')
            ->assertDontSee('private-checkout-token-stage-14')
            ->assertDontSee('password')
            ->assertDontSee('session');

        $this->actingAs($owner)->get(route('sales.receipt.show', $sale))
            ->assertDontSee('98.765,43')
            ->assertDontSee('71.234,57')
            ->assertDontSee('39.506,17')
            ->assertDontSee('private-checkout-token-stage-14');
    }

    public function test_unknown_status_falls_back_safely(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'status' => 'legacy_unknown',
        ]);

        $this->actingAs($owner)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Tidak Diketahui')
            ->assertSee('badge-neutral', false);
    }
}
