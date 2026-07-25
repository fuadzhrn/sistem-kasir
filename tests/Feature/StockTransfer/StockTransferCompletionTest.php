<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockMovement;
use App\Models\StockTransfer;

class StockTransferCompletionTest extends StockTransferTestCase
{
    public function test_owner_completes_transfer_and_creates_two_linked_movements(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '50000.00');
        $destinationStock = $this->createStock($destination, $product, '5.000', '40000.00');
        $transfer = $this->createTransfer($source, $destination, $product, $admin, [
            'quantity' => '2.000',
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertRedirect();

        $transfer->refresh();
        $this->assertSame(StockTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertSame($owner->id, $transfer->reviewed_by);
        $this->assertNotNull($transfer->completed_at);
        $this->assertSame('8.000', $sourceStock->refresh()->quantity);
        $this->assertSame('50000.00', $sourceStock->average_cost);
        $this->assertSame('7.000', $destinationStock->refresh()->quantity);
        $movements = StockMovement::query()->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $movements);
        $this->assertSame(StockMovement::TYPE_TRANSFER_OUT, $movements[0]->movement_type);
        $this->assertSame('-2.000', $movements[0]->quantity_change);
        $this->assertSame(StockMovement::TYPE_TRANSFER_IN, $movements[1]->movement_type);
        $this->assertSame('2.000', $movements[1]->quantity_change);
        $this->assertSame($movements[0]->unit_cost, $movements[1]->unit_cost);
    }

    public function test_stock_is_checked_again_when_owner_completes(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '1.000', '50000.00');
        $transfer = $this->createTransfer($source, $destination, $product, $admin, [
            'quantity' => '2.000',
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->refresh()->status);
        $this->assertSame('1.000', $sourceStock->refresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_owner_rejects_pending_transfer_without_changing_stock(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $stock = $this->createStock($source, $product);
        $transfer = $this->createTransfer($source, $destination, $product, $admin);

        $this->actingAs($owner)->patch(route('stock-transfers.reject', $transfer), [
            'rejection_reason' => 'Stok harus diprioritaskan untuk kebutuhan cabang asal.',
        ])->assertRedirect();

        $this->assertSame(StockTransfer::STATUS_REJECTED, $transfer->refresh()->status);
        $this->assertSame('10.000', $stock->refresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_rejection_requires_reason_and_closed_transfers_cannot_be_completed(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $transfer = $this->createTransfer($source, $destination, $product, $owner);

        $this->actingAs($owner)->patch(route('stock-transfers.reject', $transfer), [
            'rejection_reason' => '',
        ])->assertSessionHasErrors('rejection_reason');

        $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);
        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer))
            ->assertForbidden();

        $rejected = $this->createTransfer($source, $destination, $product, $owner, [
            'status' => StockTransfer::STATUS_REJECTED,
        ]);
        $this->actingAs($owner)->patch(route('stock-transfers.complete', $rejected))
            ->assertForbidden();
    }
}
