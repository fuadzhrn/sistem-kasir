<?php

namespace Tests\Feature\CashierDashboard;

use App\Models\Sale;

class CashierDashboardHistoryTest extends CashierDashboardTestCase
{
    public function test_history_filters_and_orders_only_own_sales(): void
    {
        $branch = $this->createBranch();
        $otherBranch = $this->createBranch('OTH');
        $cashier = $this->createUser('cashier', $branch);
        $otherCashier = $this->createUser('cashier', $branch);
        $foreignCashier = $this->createUser('cashier', $otherBranch);
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'OWN-OLD',
            'transaction_date' => '2026-07-10 09:00:00',
        ]);
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'OWN-NEW',
            'transaction_date' => '2026-07-20 09:00:00',
            'status' => Sale::STATUS_VOIDED,
        ]);
        $this->createSale($branch, $otherCashier, ['invoice_number' => 'OTHER-CASHIER']);
        $this->createSale($otherBranch, $foreignCashier, ['invoice_number' => 'OTHER-BRANCH']);

        $response = $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertOk()
            ->assertSeeInOrder(['OWN-NEW', 'OWN-OLD'])
            ->assertDontSee('OTHER-CASHIER')
            ->assertDontSee('OTHER-BRANCH');

        $response->assertSee('Jumlah Item')->assertSee('Metode Pembayaran');

        $this->actingAs($cashier)
            ->get(route('dashboard.cashier', [
                'search' => 'OWN-NEW',
                'status' => Sale::STATUS_VOIDED,
                'date_from' => '2026-07-20',
                'date_to' => '2026-07-20',
            ]))
            ->assertOk()
            ->assertSee('OWN-NEW')
            ->assertDontSee('OWN-OLD')
            ->assertSee('value="OWN-NEW"', false);
    }

    public function test_history_uses_server_side_pagination(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);

        for ($index = 1; $index <= 11; $index++) {
            $this->createSale($branch, $cashier, [
                'invoice_number' => 'PAGE-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'transaction_date' => '2026-07-20 '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).':00:00',
            ]);
        }

        $this->actingAs($cashier)
            ->get(route('dashboard.cashier', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('PAGE-11')
            ->assertDontSee('PAGE-01')
            ->assertSee('page=2', false);
    }
}
