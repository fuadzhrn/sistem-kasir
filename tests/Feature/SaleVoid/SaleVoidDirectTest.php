<?php

namespace Tests\Feature\SaleVoid;

use App\Models\Sale;
use App\Models\SaleVoid;

class SaleVoidDirectTest extends SaleVoidTestCase
{
    public function test_cashier_directly_voids_own_sale_without_approval(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('status');

        $sale->refresh();
        $this->assertSame(Sale::STATUS_VOIDED, $sale->status);
        $this->assertNotNull($sale->voided_at);
        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'voided_by' => $cashier->id,
            'status' => Sale::STATUS_VOIDED,
            'reviewed_by' => null,
        ]);
    }

    public function test_reason_and_permanent_confirmation_are_required(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), [
            'reason' => 'batal',
        ])->assertSessionHasErrors(['reason', 'confirmation']);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertDatabaseCount('sale_voids', 0);
    }

    public function test_legacy_void_requested_record_is_finalized_directly_without_approval(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        ['sale' => $sale] = $this->createVoidableSale(
            $branch,
            $admin,
            saleAttributes: ['status' => Sale::STATUS_VOID_REQUESTED],
        );
        $legacyVoid = SaleVoid::query()->create([
            'sale_id' => $sale->id,
            'requested_by' => $admin->id,
            'reason' => 'Permintaan lama yang belum diproses.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertRedirect();

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
        $this->assertSame(1, SaleVoid::query()->where('sale_id', $sale->id)->count());
        $legacyVoid->refresh();
        $this->assertSame(Sale::STATUS_VOIDED, $legacyVoid->status);
        $this->assertSame($admin->id, $legacyVoid->voided_by);
        $this->assertNull($legacyVoid->reviewed_by);
    }
}
