<?php

namespace Tests\Feature\CashierDashboard;

use App\Models\Sale;

class CashierDashboardTransactionCountTest extends CashierDashboardTestCase
{
    public function test_today_counts_only_authenticated_cashier_sales(): void
    {
        $branch = $this->createBranch();
        $cashierA = $this->createUser('cashier', $branch);
        $cashierB = $this->createUser('cashier', $branch);

        foreach (['09:00:00', '10:00:00'] as $time) {
            $this->createSale($branch, $cashierA, [
                'transaction_date' => '2026-07-25 '.$time,
            ]);
        }
        $this->createSale($branch, $cashierA, [
            'transaction_date' => '2026-07-25 11:00:00',
            'status' => Sale::STATUS_VOIDED,
        ]);
        $this->createSale($branch, $cashierA, [
            'transaction_date' => '2026-07-24 11:00:00',
        ]);
        foreach (['12:00:00', '13:00:00'] as $time) {
            $this->createSale($branch, $cashierB, [
                'transaction_date' => '2026-07-25 '.$time,
            ]);
        }

        $this->actingAs($cashierA)
            ->get(route('dashboard.cashier'))
            ->assertOk()
            ->assertSeeInOrder([
                'Nota Selesai Hari Ini',
                '>2<',
                'Nota Dibatalkan Hari Ini',
                '>1<',
                'Total Nota Dibuat Hari Ini',
                '>3<',
            ], false);
    }
}
