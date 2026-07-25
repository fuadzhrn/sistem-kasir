<?php

namespace Tests\Feature\SaleHistory;

class SaleHistoryIndexTest extends SaleHistoryTestCase
{
    public function test_all_active_roles_can_open_index_and_guest_is_redirected(): void
    {
        $branch = $this->createBranch('AAA');

        foreach ([
            $this->createUser('owner'),
            $this->createUser('admin', $branch),
            $this->createUser('cashier', $branch),
        ] as $user) {
            $this->actingAs($user)->get(route('sales.index'))->assertOk();
        }

        auth()->logout();
        $this->get(route('sales.index'))->assertRedirect(route('login'));
    }

    public function test_index_shows_latest_first_columns_actions_and_empty_state(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch, ['name' => 'Kasir Riwayat']);
        $older = $this->createSale($branch, $cashier, 'AAA-20260723-0001', [
            'transaction_date' => '2026-07-23 10:00:00',
        ]);
        $newer = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'transaction_date' => '2026-07-24 10:00:00',
        ]);

        $response = $this->actingAs($owner)->get(route('sales.index'));

        $response->assertOk()
            ->assertSeeInOrder([$newer->invoice_number, $older->invoice_number])
            ->assertSee('Kasir Riwayat')
            ->assertSee('Rp170.000')
            ->assertSee('Selesai')
            ->assertSee('Detail')
            ->assertSee('Cetak Ulang')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener"', false)
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus');

        $older->delete();
        $newer->delete();
        $this->actingAs($owner)->get(route('sales.index'))
            ->assertSee('Belum ada transaksi yang sesuai dengan filter.');
    }

    public function test_inactive_user_is_logged_out_and_redirected(): void
    {
        $branch = $this->createBranch('AAA');
        $user = $this->createUser('cashier', $branch, ['is_active' => false]);

        $this->actingAs($user)->get(route('sales.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
