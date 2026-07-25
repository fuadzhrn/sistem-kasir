<?php

namespace Tests\Feature\SaleVoid;

class SaleVoidImmutabilityTest extends SaleVoidTestCase
{
    public function test_void_preserves_sale_and_item_snapshots_and_sale_void_cannot_mutate(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        ['sale' => $sale] = $this->createVoidableSale($branch, $owner);
        $preservedSaleColumns = [
            'invoice_number',
            'transaction_date',
            'branch_id',
            'cashier_id',
            'payment_method_id',
            'subtotal',
            'discount_amount',
            'total',
            'amount_paid',
            'change_amount',
            'total_cost',
            'gross_profit',
        ];
        $saleBefore = $sale->only($preservedSaleColumns);
        $saleBefore['transaction_date'] = $sale->getRawOriginal('transaction_date');
        $itemBefore = $sale->items()->firstOrFail()->getRawOriginal();

        $this->actingAs($owner)->patch(route('sales.void', $sale), $this->voidPayload());

        $sale->refresh();
        $saleAfter = $sale->only($preservedSaleColumns);
        $saleAfter['transaction_date'] = $sale->getRawOriginal('transaction_date');
        $this->assertSame($saleBefore, $saleAfter);
        $this->assertSame($itemBefore, $sale->items()->firstOrFail()->getRawOriginal());
        $void = $sale->saleVoid()->firstOrFail();
        $originalReason = $void->reason;
        $this->assertFalse($void->update(['reason' => 'Alasan mencoba diubah setelah final.']));
        $this->assertSame($originalReason, $void->fresh()->reason);
        $this->assertFalse($void->delete());
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->id]);
        $this->assertDatabaseHas('sale_voids', ['sale_id' => $sale->id]);
    }

    public function test_no_unvoid_reopen_edit_or_approval_routes_exist(): void
    {
        foreach ([
            'sales.unvoid',
            'sales.reopen',
            'sale-voids.edit',
            'sale-voids.update',
            'sale-voids.approve',
            'sale-voids.reject',
        ] as $routeName) {
            $this->assertFalse(app('router')->has($routeName));
        }
    }
}
