<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\StockTransfer\StockTransferService;
use RuntimeException;

class StockTransferTransactionTest extends StockTransferTestCase
{
    public function test_second_movement_failure_rolls_back_both_stocks_movements_and_status(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '50000.00');
        $destinationStock = $this->createStock($destination, $product, '5.000', '40000.00');
        $transfer = $this->createTransfer($source, $destination, $product, $owner, [
            'quantity' => '2.000',
        ]);
        $movementCount = 0;

        StockMovement::creating(function () use (&$movementCount): void {
            $movementCount++;

            if ($movementCount === 2) {
                throw new RuntimeException('Simulasi kegagalan movement kedua.');
            }
        });

        try {
            app(StockTransferService::class)->complete($transfer, $owner);
            $this->fail('Transaction seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan movement kedua.', $exception->getMessage());
        } finally {
            StockMovement::flushEventListeners();
        }

        $this->assertSame('10.000', $sourceStock->refresh()->quantity);
        $this->assertSame('5.000', $destinationStock->refresh()->quantity);
        $this->assertSame('40000.00', $destinationStock->average_cost);
        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->refresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_service_uses_transactions_retry_and_deterministic_locks(): void
    {
        $source = file_get_contents(app_path('Services/StockTransfer/StockTransferService.php'));

        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("->orderBy('id')", $source);
        $this->assertStringContainsString("->orderBy('branch_id')", $source);
        $this->assertStringContainsString('insertOrIgnore(', $source);
        $this->assertStringContainsString('}, 3)', $source);
    }
}
