<?php

namespace Tests\Feature\Report;

use App\Models\Sale;

class ReceiptReportTest extends ReportTestCase
{
    public function test_receipt_report_has_one_row_per_sale_and_separates_voided_totals(): void
    {
        $branch = $this->createBranch('RRC');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, ['invoice_number' => 'NOTA-SELESAI']);
        $this->createSale($branch, $cashier, [
            'invoice_number' => 'NOTA-BATAL',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->getReport($owner, 'receipts')
            ->assertOk()
            ->assertSee('NOTA-SELESAI')
            ->assertSee('NOTA-BATAL')
            ->assertSee('Nota Selesai')
            ->assertSee('Nota Dibatalkan')
            ->assertSee('Rp180.000');
        $this->getReport($owner, 'receipts', ['status' => 'completed', 'search' => 'NOTA'])
            ->assertOk()
            ->assertSee('NOTA-SELESAI')
            ->assertDontSee('NOTA-BATAL');
        $this->getPrintReport($owner, 'receipts', ['status' => 'voided'])
            ->assertOk()
            ->assertSee('NOTA-BATAL');
    }
}
