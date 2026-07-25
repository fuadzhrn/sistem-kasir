<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Route;

class StockTransferSecurityTest extends StockTransferTestCase
{
    public function test_spoofed_status_cost_snapshots_and_audit_fields_are_ignored(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $admin = $this->createUser('admin', $source);
        $other = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $payload = $this->payload($source, $destination, $product, [
            'status' => StockTransfer::STATUS_COMPLETED,
            'unit_cost' => '1.00',
            'source_quantity_before' => '999.000',
            'destination_average_cost_after' => '1.00',
            'requested_by' => $other->id,
            'reviewed_by' => $other->id,
            'completed_at' => now(),
        ]);
        unset($payload['from_branch_id']);

        $this->actingAs($admin)->post(route('stock-transfers.store'), $payload)
            ->assertRedirect();

        $transfer = StockTransfer::query()->sole();
        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame('0.00', $transfer->unit_cost);
        $this->assertNull($transfer->source_quantity_before);
        $this->assertNull($transfer->destination_average_cost_after);
        $this->assertSame($admin->id, $transfer->requested_by);
        $this->assertNull($transfer->reviewed_by);
        $this->assertNull($transfer->completed_at);
    }

    public function test_completion_ignores_spoofed_quantity_unit_cost_and_status(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $sourceStock = $this->createStock($source, $product, '10.000', '50000.00');
        $transfer = $this->createTransfer($source, $destination, $product, $owner, [
            'quantity' => '2.000',
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.complete', $transfer), [
            'quantity' => '9.000',
            'unit_cost' => '1.00',
            'status' => StockTransfer::STATUS_REJECTED,
        ])->assertRedirect();

        $this->assertSame('8.000', $sourceStock->refresh()->quantity);
        $this->assertSame(StockTransfer::STATUS_COMPLETED, $transfer->refresh()->status);
        $this->assertSame('50000.00', $transfer->unit_cost);
    }

    public function test_admin_html_never_contains_unit_or_average_cost(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct(['purchase_price' => '918273.00']);
        $transfer = $this->createTransfer($source, $destination, $product, $admin, [
            'status' => StockTransfer::STATUS_COMPLETED,
            'unit_cost' => '876543.00',
            'destination_average_cost_before' => '765432.00',
            'destination_average_cost_after' => '654321.00',
        ]);

        $this->actingAs($admin)->get(route('stock-transfers.create'))
            ->assertOk()
            ->assertDontSee('918273')
            ->assertDontSee('average_cost')
            ->assertDontSee('unit_cost');
        $this->actingAs($admin)->get(route('stock-transfers.show', $transfer))
            ->assertOk()
            ->assertDontSee('876543')
            ->assertDontSee('765432')
            ->assertDontSee('654321')
            ->assertDontSee('Average cost');
    }

    public function test_csrf_web_middleware_exists_and_forms_have_tokens(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $transfer = $this->createTransfer($source, $destination, $product, $owner);
        $middleware = Route::getRoutes()->getByName('stock-transfers.store')?->gatherMiddleware() ?? [];

        $this->assertContains('web', $middleware);
        $this->actingAs($owner)->get(route('stock-transfers.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);
        $this->actingAs($owner)->get(route('stock-transfers.show', $transfer))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_no_edit_update_or_delete_route_exists_and_movement_types_are_fixed(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('stock-transfers.edit'));
        $this->assertNull(Route::getRoutes()->getByName('stock-transfers.update'));
        $this->assertNull(Route::getRoutes()->getByName('stock-transfers.destroy'));
        $this->assertSame('transfer_out', StockMovement::TYPE_TRANSFER_OUT);
        $this->assertSame('transfer_in', StockMovement::TYPE_TRANSFER_IN);
    }
}
