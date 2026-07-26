<?php

namespace Tests\Feature\Report;

use App\Models\Sale;

class SalesReportTest extends ReportTestCase
{
    public function test_sales_report_uses_item_snapshots_filters_and_active_totals(): void
    {
        $branch = $this->createBranch('RSL');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct('MASTER-CODE', ['name' => 'Nama Master']);
        $active = $this->createSale(
            $branch,
            $cashier,
            ['invoice_number' => 'INV-AKTIF-20'],
            $product,
            ['product_code' => 'SNAP-001', 'product_name' => 'Produk Snapshot'],
        );
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'INV-VOID-20',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->getReport($owner, 'sales')
            ->assertOk()
            ->assertSee('Produk Snapshot')
            ->assertSee('SNAP-001')
            ->assertSee('Rp200.000')
            ->assertSee('Rp20.000')
            ->assertSee('Rp180.000')
            ->assertDontSee('INV-VOID-20');

        $this->getReport($admin, 'sales', ['search' => $active['sale']->invoice_number])
            ->assertOk()
            ->assertSee('Produk Snapshot');
        $this->getReport($owner, 'sales', ['status' => 'all'])
            ->assertOk()
            ->assertSee('INV-VOID-20');
        $this->getPrintReport($owner, 'sales', ['search' => 'SNAP-001'])
            ->assertOk()
            ->assertSee('Produk Snapshot');
        $this->getReport($cashier, 'sales')->assertForbidden();
    }
}
