<?php

namespace Tests\Feature\SaleVoid;

use App\Exceptions\Sale\SaleVoidStockException;
use App\Services\Sale\SaleVoidService;

class SaleVoidTransactionTest extends SaleVoidTestCase
{
    public function test_missing_stock_rolls_back_sale_void_movement_and_activity_log(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier);
        $stock->delete();

        try {
            app(SaleVoidService::class)->voidSale(
                $sale,
                $cashier,
                $this->voidPayload()['reason'],
                false,
            );
            $this->fail('Pembatalan seharusnya gagal ketika BranchStock hilang.');
        } catch (SaleVoidStockException) {
            $this->assertSame('completed', $sale->fresh()->status);
            $this->assertDatabaseCount('sale_voids', 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertDatabaseMissing('activity_logs', ['action' => 'sale_voided']);
        }
    }

    public function test_service_source_uses_transaction_and_required_row_locks(): void
    {
        $source = file_get_contents(app_path('Services/Sale/SaleVoidService.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertGreaterThanOrEqual(3, substr_count($source, 'lockForUpdate'));
        $this->assertStringContainsString('}, 3)', $source);
    }
}
