<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use App\Models\SaleItem;

class SaleCalculationTest extends SaleTestCase
{
    public function test_server_price_and_branch_average_cost_are_the_only_sources(): void
    {
        $branch = $this->createBranch('CAL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'purchase_price' => '99999.00',
            'selling_price' => '20000.00',
        ]);
        $stock = $this->createStock($branch, $product, '10.000', '12500.00');
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($owner, $branch, $product, $payment, [
            'selling_price' => '1.00',
            'subtotal' => '2.00',
            'total' => '2.00',
            'cost_price' => '1.00',
            'total_cost' => '1.00',
            'gross_profit' => '1.00',
        ]);

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated();

        $sale = Sale::query()->sole();
        $item = SaleItem::query()->sole();
        $this->assertSame('40000.00', $sale->subtotal);
        $this->assertSame('40000.00', $sale->total);
        $this->assertSame('25000.00', $sale->total_cost);
        $this->assertSame('15000.00', $sale->gross_profit);
        $this->assertSame('20000.00', $item->selling_price);
        $this->assertSame('12500.00', $item->cost_price);
        $this->assertSame('15000.00', $item->profit);
        $this->assertSame('12500.00', $stock->refresh()->average_cost);
    }

    public function test_price_change_returns_safe_conflict_and_rolls_back_everything(): void
    {
        $branch = $this->createBranch('PRICE');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['selling_price' => '25000.00']);
        $stock = $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $response = $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'expected_subtotal' => '40000.00',
                'expected_total' => '40000.00',
            ]),
        );

        $response->assertConflict()
            ->assertJsonPath('code', 'CART_PRICE_CHANGED')
            ->assertJsonPath('data.subtotal', '50000.00')
            ->assertJsonPath('data.items.0.selling_price', '25000.00')
            ->assertJsonMissingPath('data.items.0.cost_price')
            ->assertJsonMissingPath('data.total_cost');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('10.000', $stock->refresh()->quantity);
    }

    public function test_fractional_quantity_uses_half_up_for_revenue_and_cost(): void
    {
        $branch = $this->createBranch('ROUND');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['selling_price' => '5.00']);
        $this->createStock($branch, $product, '1.000', '5.00');
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'items' => [['product_id' => $product->id, 'quantity' => '0.001']],
                'amount_received' => '1.00',
                'expected_subtotal' => '0.01',
                'expected_total' => '0.01',
            ]),
        )->assertCreated();

        $sale = Sale::query()->sole();
        $this->assertSame('0.01', $sale->subtotal);
        $this->assertSame('0.01', $sale->total_cost);
        $this->assertSame('0.00', $sale->gross_profit);
    }

    public function test_sale_item_keeps_product_unit_size_and_price_snapshots(): void
    {
        $branch = $this->createBranch('ITEM');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'code' => 'SNAP-001',
            'name' => 'Pupuk Awal',
            'size' => '1 kg',
            'selling_price' => '20000.00',
        ]);
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();
        $item = SaleItem::query()->sole();
        $product->unit->update(['name' => 'Satuan Baru']);
        $product->update([
            'code' => 'SNAP-NEW',
            'name' => 'Pupuk Baru',
            'size' => '2 kg',
            'selling_price' => '30000.00',
        ]);
        $item->refresh();

        $this->assertSame('SNAP-001', $item->product_code);
        $this->assertSame('Pupuk Awal', $item->product_name);
        $this->assertSame('Kilogram', $item->unit_name);
        $this->assertSame('1 kg', $item->product_size);
        $this->assertSame('20000.00', $item->selling_price);
        $this->assertSame('12500.00', $item->cost_price);
        $this->assertSame('2.000', $item->quantity);
    }
}
