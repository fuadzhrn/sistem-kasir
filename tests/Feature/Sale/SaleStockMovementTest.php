<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;

class SaleStockMovementTest extends SaleTestCase
{
    public function test_sale_reduces_stock_and_creates_one_immutable_movement_per_item(): void
    {
        $branch = $this->createBranch('MOV');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '2.000', '12500.00');
        $payment = $this->createPaymentMethod();

        $this->actingAs($cashier)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashier, $branch, $product, $payment),
        )->assertCreated();

        $sale = Sale::query()->sole();
        $item = SaleItem::query()->sole();
        $movement = StockMovement::query()->sole();
        $this->assertSame('0.000', $stock->refresh()->quantity);
        $this->assertSame('12500.00', $stock->average_cost);
        $this->assertSame(StockMovement::TYPE_SALE, $movement->movement_type);
        $this->assertSame(Sale::class, $movement->reference_type);
        $this->assertSame($sale->id, $movement->reference_id);
        $this->assertSame($cashier->id, $movement->created_by);
        $this->assertSame('2.000', $movement->quantity_before);
        $this->assertSame('-2.000', $movement->quantity_change);
        $this->assertSame('0.000', $movement->quantity_after);
        $this->assertSame($item->cost_price, $movement->unit_cost);
        $this->assertStringContainsString($sale->invoice_number, $movement->notes);
        $this->assertFalse($movement->update(['notes' => 'Diubah']));
        $this->assertFalse($movement->delete());
        $this->assertStringContainsString($sale->invoice_number, $movement->fresh()->notes);
    }

    public function test_insufficient_missing_or_unpriced_stock_is_rejected_safely(): void
    {
        $branch = $this->createBranch('STOCK');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertConflict()
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK')
            ->assertJsonPath('data.available_quantity', '0.000')
            ->assertJsonMissingPath('data.cost_price');

        $stock = $this->createStock($branch, $product, '1.000', '12500.00');
        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertConflict()->assertJsonPath('code', 'INSUFFICIENT_STOCK');
        $this->assertSame('1.000', $stock->refresh()->quantity);

        $stock->update(['quantity' => '5.000', 'average_cost' => '0.00']);
        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertConflict()
            ->assertJsonPath('code', 'STOCK_COST_NOT_READY')
            ->assertJsonMissingPath('data.average_cost');
    }

    public function test_inactive_product_category_or_unit_cannot_be_sold(): void
    {
        foreach (['product', 'category', 'unit'] as $index => $target) {
            $branch = $this->createBranch('IN'.$index);
            $owner = $this->createUser('owner');
            $product = $this->createProduct();
            $this->createStock($branch, $product);
            $payment = $this->createPaymentMethod(['code' => 'IN'.$index]);

            if ($target === 'product') {
                $product->update(['is_active' => false]);
            } elseif ($target === 'category') {
                $product->category->update(['is_active' => false]);
            } else {
                $product->unit->update(['is_active' => false]);
            }

            $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment),
            )->assertUnprocessable()->assertJsonPath('code', 'PRODUCT_INACTIVE');
        }
    }
}
