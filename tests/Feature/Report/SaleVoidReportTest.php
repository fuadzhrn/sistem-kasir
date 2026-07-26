<?php

namespace Tests\Feature\Report;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleVoidReportTest extends ReportTestCase
{
    public function test_sale_void_report_uses_snapshot_totals_and_does_not_mutate_original_data(): void
    {
        $branch = $this->createBranch('RSV');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createSale($branch, $cashier, [
            'invoice_number' => 'VOID-LAPORAN-001',
            'status' => Sale::STATUS_VOIDED,
        ]);
        $this->createSaleVoid($sale, $cashier, ['reason' => 'Salah input laporan']);
        $before = [
            'sales' => DB::table('sales')->count(),
            'items' => DB::table('sale_items')->count(),
            'movements' => DB::table('stock_movements')->count(),
            'activities' => DB::table('activity_logs')->count(),
        ];

        $this->getReport($owner, 'sale-voids', ['search' => 'VOID-LAPORAN'])
            ->assertOk()
            ->assertSee('VOID-LAPORAN-001')
            ->assertSee('Salah input laporan')
            ->assertSee('Rp180.000')
            ->assertSee('Rp120.000')
            ->assertSee('Rp60.000');
        $this->getPrintReport($owner, 'sale-voids', ['voided_by' => $cashier->id])
            ->assertOk()
            ->assertSee('VOID-LAPORAN-001');

        $this->assertSame($before['sales'], DB::table('sales')->count());
        $this->assertSame($before['items'], DB::table('sale_items')->count());
        $this->assertSame($before['movements'], DB::table('stock_movements')->count());
        $this->assertSame($before['activities'], DB::table('activity_logs')->count());
    }
}
