<?php

namespace Tests\Feature\Sale;

use App\Models\StockMovement;

class SaleConcurrencyTest extends SaleTestCase
{
    public function test_two_stale_cashier_checkouts_can_only_sell_last_stock_once(): void
    {
        $branch = $this->createBranch('RACE');
        $cashierA = $this->createUser('cashier', $branch);
        $cashierB = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '1.000', '12500.00');
        $payment = $this->createPaymentMethod();
        $overrides = [
            'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
            'expected_subtotal' => '20000.00',
            'expected_total' => '20000.00',
            'amount_received' => '20000.00',
        ];

        $this->actingAs($cashierA)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashierA, $branch, $product, $payment, $overrides),
        )->assertCreated();
        $this->actingAs($cashierB)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashierB, $branch, $product, $payment, $overrides),
        )->assertConflict()->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        $this->assertSame('0.000', $stock->refresh()->quantity);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_sale_service_contains_transaction_ordering_and_row_locks(): void
    {
        $source = file_get_contents(app_path('Services/Sale/SaleService.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("->orderBy('product_id')", $source);
        $this->assertStringContainsString('array_unique($productIds)', $source);
        $this->assertStringContainsString('}, 3)', $source);
    }

    public function test_multi_product_persistence_follows_ascending_product_lock_order(): void
    {
        $branch = $this->createBranch('ORDER');
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'ORDER-A']);
        $productB = $this->createProduct(['code' => 'ORDER-B']);
        $this->createStock($branch, $productA);
        $this->createStock($branch, $productB);
        $payment = $this->createPaymentMethod();
        $items = [
            ['product_id' => $productB->id, 'quantity' => '1.000'],
            ['product_id' => $productA->id, 'quantity' => '1.000'],
        ];

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $productA, $payment, [
                'items' => $items,
                'expected_subtotal' => '40000.00',
                'expected_total' => '40000.00',
            ]),
        )->assertCreated();

        $this->assertSame(
            [$productA->id, $productB->id],
            StockMovement::query()->orderBy('id')->pluck('product_id')->all(),
        );
    }
}
