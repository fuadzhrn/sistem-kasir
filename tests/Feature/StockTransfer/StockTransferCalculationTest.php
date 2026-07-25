<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockTransfer;

class StockTransferCalculationTest extends StockTransferTestCase
{
    public function test_destination_average_cost_uses_weighted_average_and_source_cost_stays_same(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '60000.00');
        $destinationStock = $this->createStock($destination, $product, '10.000', '40000.00');
        $transfer = $this->createTransfer($source, $destination, $product, $owner, [
            'quantity' => '5.000',
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertRedirect();

        $this->assertSame('60000.00', $sourceStock->refresh()->average_cost);
        $this->assertSame('46666.67', $destinationStock->refresh()->average_cost);
        $this->assertSame('60000.00', $transfer->refresh()->unit_cost);
        $this->assertSame('40000.00', $transfer->destination_average_cost_before);
        $this->assertSame('46666.67', $transfer->destination_average_cost_after);
    }

    public function test_empty_destination_uses_source_average_cost(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($source, $product, '10.000', '37500.00');
        $transfer = $this->createTransfer($source, $destination, $product, $owner, [
            'quantity' => '2.500',
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_stocks', [
            'branch_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => '2.500',
            'average_cost' => '37500.00',
        ]);
    }

    public function test_zero_source_average_cost_is_rejected_without_partial_change(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '0.00');
        $transfer = $this->createTransfer($source, $destination, $product, $owner);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertSessionHasErrors('status');

        $this->assertSame('10.000', $sourceStock->refresh()->quantity);
        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->refresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
