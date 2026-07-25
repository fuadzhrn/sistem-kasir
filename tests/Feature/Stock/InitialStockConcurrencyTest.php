<?php

namespace Tests\Feature\Stock;

use App\Models\BranchStock;
use App\Models\StockMovement;
use App\Services\Stock\StockService;
use Illuminate\Database\QueryException;
use RuntimeException;

class InitialStockConcurrencyTest extends StockTestCase
{
    public function test_unique_branch_and_product_constraint_prevents_duplicate_stock_rows(): void
    {
        $branch = $this->createBranch('CC01');
        $product = $this->createProduct();
        $this->createStock($branch, $product);

        $this->expectException(QueryException::class);
        $this->createStock($branch, $product, '20.000');
    }

    public function test_repeated_service_calls_reuse_one_stock_row_and_preserve_each_movement(): void
    {
        $branch = $this->createBranch('CC02');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $service = app(StockService::class);

        $service->setInitialStock($branch, $product, '10.000', 'Input pertama', $owner);
        $service->setInitialStock($branch, $product, '12.000', 'Koreksi kedua', $owner);

        $this->assertDatabaseCount('branch_stocks', 1);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertSame('12.000', BranchStock::query()->sole()->quantity);
    }

    public function test_movement_failure_rolls_back_stock_creation_and_quantity_change(): void
    {
        $branch = $this->createBranch('CC03');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $service = app(StockService::class);

        StockMovement::creating(function (): never {
            throw new RuntimeException('Simulasi kegagalan movement.');
        });

        try {
            $service->setInitialStock($branch, $product, '10.000', 'Input akan gagal', $owner);
            $this->fail('Exception transaction seharusnya terjadi.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan movement.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_service_uses_transaction_row_lock_and_atomic_insert_strategy(): void
    {
        $source = file_get_contents(app_path('Services/Stock/StockService.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('insertOrIgnore(', $source);
        $this->assertStringContainsString('StockMovement::query()->create(', $source);
    }
}
