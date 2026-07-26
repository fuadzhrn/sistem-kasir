<?php

namespace Tests\Feature\Report;

use App\Models\Sale;

class TopProductReportTest extends ReportTestCase
{
    public function test_top_products_ranks_only_completed_sales_and_uses_snapshots(): void
    {
        $branch = $this->createBranch('RTP');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct('TOP-MASTER');
        $this->createSale(
            $branch,
            $cashier,
            ['invoice_number' => 'TOP-AKTIF'],
            $product,
            ['product_code' => 'TOP-SNAPSHOT', 'product_name' => 'Produk Terlaris Snapshot'],
        );
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'TOP-VOID',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->getReport($owner, 'top-products')
            ->assertOk()
            ->assertSee('Produk Terlaris Snapshot')
            ->assertSee('TOP-SNAPSHOT')
            ->assertSee('Rp180.000');
        $this->getReport($owner, 'top-products', ['search' => 'Snapshot'])
            ->assertOk()
            ->assertSee('Produk Terlaris Snapshot');
        $this->getPrintReport($owner, 'top-products')->assertOk();
    }
}
