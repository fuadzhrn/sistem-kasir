<?php

namespace Tests\Feature\CashierDashboard;

use App\Models\BranchStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;

class CashierDashboardReceiptTest extends CashierDashboardTestCase
{
    public function test_cashier_can_reprint_own_receipt_without_mutating_data(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createSale($branch, $cashier, [
            'invoice_number' => 'PRINT-ME',
        ]);
        $counts = [
            Sale::class => Sale::query()->count(),
            SaleItem::class => SaleItem::query()->count(),
            BranchStock::class => BranchStock::query()->count(),
            StockMovement::class => StockMovement::query()->count(),
        ];

        $this->actingAs($cashier)
            ->get(route('receipts.print', ['sale' => $sale, 'copy' => 1]))
            ->assertOk()
            ->assertSee('PRINT-ME');

        foreach ($counts as $model => $count) {
            $this->assertSame($count, $model::query()->count());
        }

        $this->actingAs($cashier)
            ->get(route('dashboard.cashier'))
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener"', false);
    }
}
