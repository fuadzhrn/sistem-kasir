<?php

namespace Tests\Feature\StockReceipt;

use App\Models\StockMovement;
use App\Services\StockReceipt\StockReceiptService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StockReceiptTransactionTest extends StockReceiptTestCase
{
    public function test_failure_on_second_movement_rolls_back_header_items_stocks_and_movements(): void
    {
        $branch = $this->createBranch('ROLL');
        $owner = $this->createUser('owner');
        $first = $this->createProduct(['code' => 'ROLL-1']);
        $second = $this->createProduct(['code' => 'ROLL-2']);
        $firstStock = $this->createStock($branch, $first, '10.000', '50000.00');
        $secondStock = $this->createStock($branch, $second, '2.000', '20000.00');
        $movementCount = 0;

        StockMovement::creating(function () use (&$movementCount): void {
            $movementCount++;

            if ($movementCount === 2) {
                throw new RuntimeException('Simulasi kegagalan movement kedua.');
            }
        });

        try {
            app(StockReceiptService::class)->create(
                $branch,
                CarbonImmutable::parse('2026-07-25'),
                'Supplier Rollback',
                null,
                [
                    ['product_id' => $first->id, 'quantity' => '5', 'purchase_price' => '60000'],
                    ['product_id' => $second->id, 'quantity' => '3', 'purchase_price' => '25000'],
                ],
                $owner,
            );
            $this->fail('Transaction seharusnya melempar exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan movement kedua.', $exception->getMessage());
        } finally {
            StockMovement::flushEventListeners();
        }

        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertDatabaseCount('stock_receipt_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('10.000', $firstStock->refresh()->quantity);
        $this->assertSame('50000.00', $firstStock->average_cost);
        $this->assertSame('2.000', $secondStock->refresh()->quantity);
        $this->assertSame('20000.00', $secondStock->average_cost);
    }

    public function test_inactive_product_failure_leaves_no_partial_document(): void
    {
        $branch = $this->createBranch('ROLL2');
        $owner = $this->createUser('owner');
        $active = $this->createProduct();
        $inactive = $this->createProduct(['is_active' => false]);

        try {
            app(StockReceiptService::class)->create(
                $branch,
                CarbonImmutable::parse('2026-07-25'),
                null,
                null,
                [
                    ['product_id' => $active->id, 'quantity' => '1', 'purchase_price' => '10000'],
                    ['product_id' => $inactive->id, 'quantity' => '1', 'purchase_price' => '10000'],
                ],
                $owner,
            );
            $this->fail('Produk nonaktif seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertDatabaseCount('stock_receipt_items', 0);
        $this->assertDatabaseCount('branch_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
