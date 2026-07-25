<?php

namespace Tests\Feature\SaleHistory;

class SaleDetailTest extends SaleHistoryTestCase
{
    public function test_detail_uses_snapshot_and_shows_complete_payment_information(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch, ['name' => 'Kasir Detail']);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'notes' => 'Catatan aman',
        ]);

        $this->actingAs($owner)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('AAA-20260724-0001')
            ->assertSee('24 Juli 2026, 14.35')
            ->assertSee('Cabang AAA')
            ->assertSee('Kasir Detail')
            ->assertSee('Pupuk Snapshot')
            ->assertSee('SNAP-001')
            ->assertSee('50 kg')
            ->assertSee('2,5')
            ->assertSee('Rp70.000')
            ->assertSee('Rp170.000')
            ->assertSee('Rp200.000')
            ->assertSee('Rp30.000')
            ->assertSee('Catatan aman');
    }

    public function test_owner_and_admin_see_internal_section_but_cashier_does_not(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $this->actingAs($owner)->get(route('sales.show', $sale))
            ->assertSee('Ringkasan Internal')->assertSee('Total HPP')->assertSee('Laba kotor');
        $this->actingAs($admin)->get(route('sales.show', $sale))
            ->assertSee('Ringkasan Internal')->assertSee('Total HPP')->assertSee('Laba kotor');
        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertDontSee('Ringkasan Internal')->assertDontSee('Total HPP')->assertDontSee('Laba kotor');
    }

    public function test_snapshot_output_is_escaped(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'notes' => '<script>alert("catatan")</script>',
        ]);
        $sale->items()->update(['product_name' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($owner)->get(route('sales.show', $sale))
            ->assertSee('&lt;script&gt;', false)
            ->assertSee('&lt;img', false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('<img src=x', false);
    }
}
