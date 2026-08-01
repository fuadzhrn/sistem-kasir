<?php

namespace Tests\Feature\SaleHistory;

use App\Models\Sale;

class SaleReceiptPreviewTest extends SaleHistoryTestCase
{
    public function test_preview_uses_print_layout_snapshot_and_has_no_internal_cost_or_print_call(): void
    {
        $branch = $this->createBranch('AAA', ['address' => 'Alamat Cabang', 'phone' => '081234567']);
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch, ['name' => 'Kasir Preview']);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $this->actingAs($owner)->get(route('sales.receipt.show', $sale))
            ->assertOk()
            ->assertSee('print-document', false)
            ->assertSee('AAA-20260724-0001')
            ->assertSee('Alamat Cabang')
            ->assertSee('081234567')
            ->assertSee('Kasir Preview')
            ->assertSee('Pupuk Snapshot')
            ->assertSee('Rp170.000')
            ->assertSee('Pratinjau menggunakan data transaksi yang tersimpan.')
            ->assertDontSee('Total HPP')
            ->assertDontSee('Laba kotor')
            ->assertDontSee('checkout_token')
            ->assertDontSee('window.print', false);
    }

    public function test_preview_displays_void_status_labels(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $requested = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'status' => Sale::STATUS_VOID_REQUESTED,
        ]);
        $voided = $this->createSale($branch, $cashier, 'AAA-20260724-0002', [
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->actingAs($owner)->get(route('sales.receipt.show', $requested))
            ->assertSee('MENUNGGU PEMBATALAN');
        $this->actingAs($owner)->get(route('sales.receipt.show', $voided))
            ->assertSee('TRANSAKSI DIBATALKAN');
    }
}
