<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\StockAdjustment\StockAdjustmentService;
use RuntimeException;

class StockAdjustmentTransactionTest extends StockAdjustmentTestCase
{
    public function test_movement_failure_rolls_back_document_and_stock_change(): void
    {
        $branch = $this->createBranch('ROLL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '10.000', '50000.00');

        StockMovement::creating(function (): never {
            throw new RuntimeException('Simulasi kegagalan movement.');
        });

        try {
            app(StockAdjustmentService::class)->create(
                $branch,
                $product,
                StockAdjustment::TYPE_ADDITION,
                '2.000',
                null,
                'Alasan transaction rollback yang dapat diaudit.',
                $owner,
            );
            $this->fail('Transaction seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan movement.', $exception->getMessage());
        } finally {
            StockMovement::flushEventListeners();
        }

        $this->assertSame('10.000', $stock->refresh()->quantity);
        $this->assertSame('50000.00', $stock->average_cost);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_service_uses_transaction_locks_retry_and_atomic_movement(): void
    {
        $source = file_get_contents(app_path('Services/StockAdjustment/StockAdjustmentService.php'));
        $numberSource = file_get_contents(app_path('Services/StockAdjustment/StockAdjustmentNumberService.php'));

        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('insertOrIgnore(', $source);
        $this->assertStringContainsString('StockMovement::query()->create(', $source);
        $this->assertStringContainsString('}, 3)', $source);
        $this->assertStringContainsString('lockForUpdate()', $numberSource);
    }
}
