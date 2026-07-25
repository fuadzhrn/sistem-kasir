<?php

namespace Tests\Feature\SaleVoid;

class SaleVoidFrontendTest extends SaleVoidTestCase
{
    public function test_authorized_completed_sale_has_direct_void_modal_and_required_markup(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier, 'non_cash');

        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Batalkan Transaksi')
            ->assertSee('name="reason"', false)
            ->assertSee('name="confirmation"', false)
            ->assertSee('name="refund_confirmed"', false)
            ->assertSee('tidak otomatis mengembalikan dana QRIS atau transfer')
            ->assertSee('_method', false)
            ->assertSee('PATCH')
            ->assertSee('assets/js/pages/sale-void.js')
            ->assertDontSee('Ajukan Pembatalan')
            ->assertDontSee('Setujui Pembatalan')
            ->assertDontSee('Tolak Pembatalan')
            ->assertDontSee('onclick=', false);
    }

    public function test_voided_sale_hides_action_and_shows_history_on_detail_and_receipt(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);
        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());

        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Transaksi Dibatalkan')
            ->assertSee('Pelanggan membatalkan seluruh transaksi')
            ->assertDontSee('data-sale-void-open', false);
        $this->actingAs($cashier)->get(route('receipts.print', ['sale' => $sale, 'copy' => 1]))
            ->assertOk()
            ->assertSee('TRANSAKSI DIBATALKAN')
            ->assertSee('Alasan pembatalan');
    }
}
