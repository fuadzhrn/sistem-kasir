<?php

namespace Tests\Feature\SaleVoid;

use App\Models\Sale;

class SaleVoidNonCashTest extends SaleVoidTestCase
{
    public function test_qris_requires_manual_refund_confirmation_before_any_mutation(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale($branch, $cashier, 'non_cash');

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertSessionHasErrors('refund_confirmed');
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertSame('10.000', $stock->fresh()->quantity);
        $this->assertDatabaseCount('sale_voids', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload(true))
            ->assertRedirect();
        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'refund_confirmed' => true,
        ]);
    }

    public function test_cash_payment_does_not_require_refund_confirmation(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertRedirect();
        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'refund_confirmed' => false,
        ]);
    }

    public function test_other_payment_type_also_requires_confirmation(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier, 'other');

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertSessionHasErrors('refund_confirmed');
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
    }

    public function test_bank_transfer_uses_the_same_manual_refund_confirmation_rule(): void
    {
        $branch = $this->createBranch('VTR');
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale, 'payment' => $payment] = $this->createVoidableSale($branch, $cashier, 'non_cash');
        $payment->update(['name' => 'Transfer Bank']);
        $sale->update(['payment_method_name' => 'Transfer Bank']);

        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload())
            ->assertSessionHasErrors('refund_confirmed');
        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload(true))
            ->assertRedirect();
        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'payment_method_name' => 'Transfer Bank',
            'refund_confirmed' => true,
        ]);
    }
}
