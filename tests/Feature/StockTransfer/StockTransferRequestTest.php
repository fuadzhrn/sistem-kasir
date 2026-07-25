<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockTransfer;

class StockTransferRequestTest extends StockTransferTestCase
{
    public function test_admin_creates_pending_request_from_own_branch_without_stock_change_or_movement(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '50000.00');
        $payload = $this->payload($source, $destination, $product);
        unset($payload['from_branch_id']);

        $this->actingAs($admin)->post(route('stock-transfers.store'), $payload)
            ->assertRedirect();

        $transfer = StockTransfer::query()->sole();
        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame($source->id, $transfer->from_branch_id);
        $this->assertSame($admin->id, $transfer->requested_by);
        $this->assertSame('10.000', $sourceStock->refresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseMissing('branch_stocks', [
            'branch_id' => $destination->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_source_and_destination_must_differ_and_quantity_must_be_positive(): void
    {
        $branch = $this->createBranch('SAME');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-transfers.store'), $this->payload(
            $branch,
            $branch,
            $product,
            ['quantity' => '0'],
        ))->assertSessionHasErrors(['to_branch_id', 'quantity']);

        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_inactive_branch_or_product_is_rejected(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('OFF', ['is_active' => false]);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-transfers.store'), $this->payload(
            $source,
            $destination,
            $product,
        ))->assertSessionHasErrors('to_branch_id');

        $destination->update(['is_active' => true]);
        $product->update(['is_active' => false]);

        $this->actingAs($owner)->post(route('stock-transfers.store'), $this->payload(
            $source,
            $destination,
            $product,
        ))->assertSessionHasErrors('product_id');
    }

    public function test_notes_and_quantity_precision_are_validated(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-transfers.store'), $this->payload(
            $source,
            $destination,
            $product,
            ['quantity' => '1.0001', 'notes' => 'pendek'],
        ))->assertSessionHasErrors(['quantity', 'notes']);
    }

    public function test_transfer_number_is_backend_generated_unique_and_sequential_per_route_and_date(): void
    {
        $source = $this->createBranch('NUM');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $payload = $this->payload($source, $destination, $product, [
            'transfer_number' => 'NOMOR-PALSU',
        ]);

        $this->actingAs($owner)->post(route('stock-transfers.store'), $payload)->assertRedirect();
        $this->actingAs($owner)->post(route('stock-transfers.store'), $payload)->assertRedirect();

        $numbers = StockTransfer::query()->orderBy('id')->pluck('transfer_number')->all();
        $prefix = 'TRF-NUM-DST-'.now()->format('Ymd').'-';
        $this->assertSame([$prefix.'0001', $prefix.'0002'], $numbers);
        $this->assertNotContains('NOMOR-PALSU', $numbers);
    }
}
