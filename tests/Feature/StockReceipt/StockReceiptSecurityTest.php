<?php

namespace Tests\Feature\StockReceipt;

use App\Models\StockMovement;
use App\Models\StockReceipt;
use Illuminate\Support\Facades\Route;

class StockReceiptSecurityTest extends StockReceiptTestCase
{
    public function test_spoofed_backend_fields_are_ignored_and_server_values_win(): void
    {
        $branch = $this->createBranch('SEC');
        $admin = $this->createUser('admin', $branch);
        $otherUser = $this->createUser('admin', $branch);
        $product = $this->createProduct();
        $payload = $this->payload($branch, $product);
        unset($payload['branch_id']);
        $payload += [
            'receipt_number' => 'PALSU',
            'total_cost' => '1.00',
            'created_by' => $otherUser->id,
            'average_cost' => '1.00',
            'movement_type' => StockMovement::TYPE_SALE,
            'reference_id' => 999999,
        ];
        $payload['items'][0] += [
            'subtotal' => '1.00',
            'quantity_before' => '999.000',
            'quantity_after' => '999.000',
            'average_cost_before' => '1.00',
            'average_cost_after' => '1.00',
            'unit_cost' => '1.00',
        ];

        $this->actingAs($admin)->post(route('stock-receipts.store'), $payload)->assertRedirect();

        $receipt = StockReceipt::query()->sole();
        $movement = StockMovement::query()->sole();
        $this->assertSame($admin->id, $receipt->created_by);
        $this->assertSame('300000.00', $receipt->total_cost);
        $this->assertNotSame('PALSU', $receipt->receipt_number);
        $this->assertSame(StockMovement::TYPE_PURCHASE, $movement->movement_type);
        $this->assertSame($receipt->id, $movement->reference_id);
        $this->assertSame('5.000', $movement->quantity_after);
        $this->assertSame('60000.00', $movement->unit_cost);
    }

    public function test_admin_response_never_contains_cost_snapshot_fields_or_master_purchase_price(): void
    {
        $branch = $this->createBranch('HIDE');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct(['purchase_price' => '918273.00']);
        $receipt = $this->createReceipt($branch, $admin, $product);

        $this->actingAs($admin)->get(route('stock-receipts.create'))
            ->assertOk()
            ->assertDontSee('918273')
            ->assertDontSee('average_cost');

        $this->actingAs($admin)->get(route('stock-receipts.show', $receipt))
            ->assertOk()
            ->assertDontSee('average_cost')
            ->assertDontSee('Harga modal rata-rata')
            ->assertSee('Harga beli penerimaan');
    }

    public function test_csrf_active_records_and_roles_are_enforced(): void
    {
        $branch = $this->createBranch('CSRF');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();

        $middleware = Route::getRoutes()->getByName('stock-receipts.store')?->gatherMiddleware() ?? [];
        $this->assertContains('web', $middleware);
        $this->actingAs($owner)->get(route('stock-receipts.create'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->actingAs($cashier)->get(route('stock-receipts.index'))->assertForbidden();

        $branch->update(['is_active' => false]);
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product))
            ->assertSessionHasErrors('branch_id');

        $branch->update(['is_active' => true]);
        $product->update(['is_active' => false]);
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product))
            ->assertSessionHasErrors('items.0.product_id');
    }
}
