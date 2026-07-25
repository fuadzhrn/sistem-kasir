<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\PriceHistory;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use PHPUnit\Framework\Attributes\DataProvider;

class StockAdjustmentCalculationTest extends StockAdjustmentTestCase
{
    #[DataProvider('standardAdjustmentCases')]
    public function test_standard_types_change_stock_and_create_matching_audit(
        string $type,
        string $expectedChange,
        string $expectedAfter,
        string $movementType,
    ): void {
        $branch = $this->createBranch('CAL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '10.000', '50000.00');

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'adjustment_type' => $type,
            'quantity' => '2.000',
        ]))->assertRedirect();

        $adjustment = StockAdjustment::query()->sole();
        $movement = StockMovement::query()->sole();
        $this->assertSame($expectedAfter, $stock->refresh()->quantity);
        $this->assertSame('50000.00', $stock->average_cost);
        $this->assertSame('10.000', $adjustment->quantity_before);
        $this->assertSame($expectedChange, $adjustment->quantity_change);
        $this->assertSame($expectedAfter, $adjustment->quantity_after);
        $this->assertSame('50000.00', $adjustment->unit_cost);
        $this->assertSame($movementType, $movement->movement_type);
        $this->assertSame(StockAdjustment::class, $movement->reference_type);
        $this->assertSame($adjustment->id, $movement->reference_id);
        $this->assertSame($expectedChange, $movement->quantity_change);
        $this->assertStringContainsString($adjustment->adjustment_number, $movement->notes);
        $this->assertStringContainsString($adjustment->type_label, $movement->notes);
    }

    public static function standardAdjustmentCases(): array
    {
        return [
            'addition' => [
                StockAdjustment::TYPE_ADDITION,
                '2.000',
                '12.000',
                StockMovement::TYPE_ADJUSTMENT_IN,
            ],
            'subtraction' => [
                StockAdjustment::TYPE_SUBTRACTION,
                '-2.000',
                '8.000',
                StockMovement::TYPE_ADJUSTMENT_OUT,
            ],
            'damaged' => [
                StockAdjustment::TYPE_DAMAGED,
                '-2.000',
                '8.000',
                StockMovement::TYPE_ADJUSTMENT_OUT,
            ],
            'lost' => [
                StockAdjustment::TYPE_LOST,
                '-2.000',
                '8.000',
                StockMovement::TYPE_ADJUSTMENT_OUT,
            ],
        ];
    }

    public function test_correction_up_down_and_no_change_are_handled_correctly(): void
    {
        $branch = $this->createBranch('COR');
        $owner = $this->createUser('owner');
        $upProduct = $this->createProduct(['code' => 'COR-UP']);
        $downProduct = $this->createProduct(['code' => 'COR-DOWN']);
        $sameProduct = $this->createProduct(['code' => 'COR-SAME']);
        $this->createStock($branch, $upProduct, '10.000');
        $this->createStock($branch, $downProduct, '10.000');
        $sameStock = $this->createStock($branch, $sameProduct, '10.000');

        $correction = fn ($product, string $target): array => $this->payload($branch, $product, [
            'adjustment_type' => StockAdjustment::TYPE_CORRECTION,
            'quantity' => null,
            'target_quantity' => $target,
        ]);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $correction($upProduct, '12.500'))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-adjustments.store'), $correction($downProduct, '7.250'))
            ->assertRedirect();
        $this->actingAs($owner)->post(route('stock-adjustments.store'), $correction($sameProduct, '10.000'))
            ->assertSessionHasErrors('target_quantity');

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $upProduct->id,
            'quantity' => '2.500',
            'target_quantity' => '12.500',
            'quantity_change' => '2.500',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $upProduct->id,
            'movement_type' => StockMovement::TYPE_ADJUSTMENT_IN,
        ]);
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $downProduct->id,
            'quantity' => '2.750',
            'target_quantity' => '7.250',
            'quantity_change' => '-2.750',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $downProduct->id,
            'movement_type' => StockMovement::TYPE_ADJUSTMENT_OUT,
        ]);
        $this->assertSame('10.000', $sameStock->refresh()->quantity);
        $this->assertDatabaseMissing('stock_adjustments', ['product_id' => $sameProduct->id]);
    }

    public function test_fractional_quantity_and_reference_cost_fallback_are_exact_without_changing_average(): void
    {
        $branch = $this->createBranch('FRAC');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['purchase_price' => '24000.00']);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'quantity' => '1.250',
        ]))->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '1.250',
            'average_cost' => '0.00',
        ]);
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'quantity_change' => '1.250',
            'unit_cost' => '24000.00',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'unit_cost' => '24000.00',
        ]);
    }

    public function test_addition_without_any_reference_cost_is_rejected_and_products_stay_unchanged(): void
    {
        $branch = $this->createBranch('ZERO');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'purchase_price' => '0.00',
            'selling_price' => '12345.00',
            'minimum_stock' => '7.000',
        ]);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product))
            ->assertSessionHasErrors('quantity');

        $product->refresh();
        $this->assertSame('0.00', $product->purchase_price);
        $this->assertSame('12345.00', $product->selling_price);
        $this->assertSame('7.000', $product->minimum_stock);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('branch_stocks', 0);
        $this->assertSame(0, PriceHistory::query()->count());
    }

    public function test_reduction_above_available_stock_is_rejected_without_negative_stock(): void
    {
        $branch = $this->createBranch('NEG');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '2.000');

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'adjustment_type' => StockAdjustment::TYPE_SUBTRACTION,
            'quantity' => '2.001',
        ]))->assertSessionHasErrors('quantity');

        $this->assertSame('2.000', $stock->refresh()->quantity);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
