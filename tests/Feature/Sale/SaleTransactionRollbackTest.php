<?php

namespace Tests\Feature\Sale;

use App\Models\ActivityLog;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SaleTransactionRollbackTest extends SaleTestCase
{
    public function test_one_invalid_item_rolls_back_entire_multi_item_checkout(): void
    {
        $branch = $this->createBranch('ROLL');
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'ROLL-A']);
        $productB = $this->createProduct(['code' => 'ROLL-B']);
        $stockA = $this->createStock($branch, $productA, '5.000');
        $stockB = $this->createStock($branch, $productB, '1.000');
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($owner, $branch, $productA, $payment, [
            'items' => [
                ['product_id' => $productA->id, 'quantity' => '2.000'],
                ['product_id' => $productB->id, 'quantity' => '2.000'],
            ],
            'expected_subtotal' => '80000.00',
            'expected_total' => '80000.00',
            'amount_received' => '100000.00',
        ]);

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertConflict()
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame('5.000', $stockA->refresh()->quantity);
        $this->assertSame('1.000', $stockB->refresh()->quantity);
    }

    public function test_price_conflict_also_has_no_partial_database_state(): void
    {
        $branch = $this->createBranch('PCR');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['selling_price' => '30000.00']);
        $stock = $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertConflict()->assertJsonPath('code', 'CART_PRICE_CHANGED');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame('10.000', $stock->refresh()->quantity);
    }

    public function test_stock_movement_failure_rolls_back_sale_items_and_stock(): void
    {
        $branch = $this->createBranch('MFAIL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        Log::spy();
        StockMovement::creating(function (): never {
            throw new RuntimeException('Simulasi kegagalan movement.');
        });

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertInternalServerError()
            ->assertJsonPath('code', 'CHECKOUT_FAILED')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame('10.000', $stock->refresh()->quantity);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_activity_log_failure_rolls_back_everything(): void
    {
        $branch = $this->createBranch('LFAIL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        Log::spy();
        ActivityLog::creating(function (): never {
            throw new RuntimeException('Simulasi kegagalan activity log.');
        });

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertInternalServerError()->assertJsonPath('code', 'CHECKOUT_FAILED');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame('10.000', $stock->refresh()->quantity);
        Log::shouldHaveReceived('error')->once();
    }
}
