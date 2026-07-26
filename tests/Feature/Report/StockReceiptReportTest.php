<?php

namespace Tests\Feature\Report;

class StockReceiptReportTest extends ReportTestCase
{
    public function test_stock_receipt_report_filters_document_supplier_product_and_branch(): void
    {
        $branch = $this->createBranch('RSR');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct('BARANG-MASUK');
        $receipt = $this->createStockReceipt($branch, $admin, $product, [
            'receipt_number' => 'BM-LAPORAN-001',
            'supplier_name' => 'Supplier Hijau',
        ]);

        $this->getReport($owner, 'stock-receipts', [
            'supplier' => 'Hijau',
            'product_id' => $product->id,
        ])->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee('Supplier Hijau')
            ->assertSee('Rp60.000');
        $this->getReport($admin, 'stock-receipts', ['search' => 'BARANG-MASUK'])
            ->assertOk()
            ->assertSee($receipt->receipt_number);
        $this->getPrintReport($owner, 'stock-receipts')->assertOk()->assertSee('BM-LAPORAN-001');
    }
}
