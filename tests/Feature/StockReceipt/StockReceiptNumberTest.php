<?php

namespace Tests\Feature\StockReceipt;

use App\Models\StockReceipt;
use Illuminate\Database\QueryException;

class StockReceiptNumberTest extends StockReceiptTestCase
{
    public function test_number_uses_branch_date_and_independent_daily_sequence(): void
    {
        $branchA = $this->createBranch('CBG-01');
        $branchB = $this->createBranch('CBG02');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchA, $product))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchA, $product))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchB, $product))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchA, $product, [
            'receipt_date' => '2026-07-24',
        ]))->assertRedirect();

        $this->assertDatabaseHas('stock_receipts', ['receipt_number' => 'BM-CBG01-20260725-0001']);
        $this->assertDatabaseHas('stock_receipts', ['receipt_number' => 'BM-CBG01-20260725-0002']);
        $this->assertDatabaseHas('stock_receipts', ['receipt_number' => 'BM-CBG02-20260725-0001']);
        $this->assertDatabaseHas('stock_receipts', ['receipt_number' => 'BM-CBG01-20260724-0001']);
        $this->assertSame(4, StockReceipt::query()->distinct()->count('receipt_number'));
    }

    public function test_browser_cannot_choose_receipt_number_and_database_rejects_duplicates(): void
    {
        $branch = $this->createBranch('NUM');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'receipt_number' => 'PALSU-999',
        ]))->assertRedirect();

        $receipt = StockReceipt::query()->sole();
        $this->assertSame('BM-NUM-20260725-0001', $receipt->receipt_number);

        $this->expectException(QueryException::class);
        StockReceipt::query()->create([
            'branch_id' => $branch->id,
            'receipt_number' => $receipt->receipt_number,
            'receipt_date' => '2026-07-25',
            'supplier_name' => null,
            'total_cost' => '0.00',
            'notes' => null,
            'created_by' => $owner->id,
        ]);
    }

    public function test_number_service_uses_locks_unique_constraint_and_transaction_retry(): void
    {
        $numberSource = file_get_contents(app_path('Services/StockReceipt/StockReceiptNumberService.php'));
        $receiptSource = file_get_contents(app_path('Services/StockReceipt/StockReceiptService.php'));
        $migrationSource = file_get_contents(database_path('migrations/2026_07_25_000800_create_stock_receipts_table.php'));

        $this->assertStringContainsString('lockForUpdate()', $numberSource);
        $this->assertStringContainsString('DB::transaction(', $receiptSource);
        $this->assertStringContainsString('}, 3)', $receiptSource);
        $this->assertStringContainsString('->unique()', $migrationSource);
    }
}
