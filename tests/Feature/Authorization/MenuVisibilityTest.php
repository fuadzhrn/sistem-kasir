<?php

namespace Tests\Feature\Authorization;

class MenuVisibilityTest extends AuthorizationTestCase
{
    public function test_owner_sidebar_contains_active_branch_and_user_links(): void
    {
        $owner = $this->createUser('owner');

        $response = $this->actingAs($owner)->get(route('account.index'))->assertOk();

        foreach (['Dashboard', 'Kasir', 'Nota', 'Produk', 'Stok', 'Pengeluaran', 'Laporan', 'Cabang', 'Pengguna', 'Aktivitas', 'Pengaturan', 'Akun Saya'] as $label) {
            $response->assertSeeText($label);
        }

        $response
            ->assertSee('href="'.route('branches.index').'"', false)
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertDontSee('href="/settings"', false);
    }

    public function test_admin_sidebar_contains_branch_menu_and_hides_owner_only_items(): void
    {
        $admin = $this->createUser('admin', $this->createBranch('MNA'));

        $response = $this->actingAs($admin)->get(route('account.index'))->assertOk();

        foreach (['Dashboard Cabang', 'Kasir', 'Nota Cabang', 'Produk', 'Stok Cabang', 'Pengeluaran Cabang', 'Laporan Cabang', 'Cabang Saya', 'Pegawai Cabang', 'Akun Saya'] as $label) {
            $response->assertSeeText($label);
        }

        $response
            ->assertSee('href="'.route('my-branch.show').'"', false)
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertDontSeeText('Pengaturan')
            ->assertDontSeeText('Aktivitas');
    }

    public function test_cashier_sidebar_only_contains_cashier_work_menu(): void
    {
        $cashier = $this->createUser('cashier', $this->createBranch('MNK'));

        $response = $this->actingAs($cashier)->get(route('account.index'))->assertOk();

        foreach (['Transaksi Baru', 'Transaksi Saya', 'Cetak Ulang Nota', 'Akun Saya'] as $label) {
            $response->assertSeeText($label);
        }

        $response
            ->assertDontSeeText('Pengaturan')
            ->assertDontSeeText('Aktivitas')
            ->assertDontSeeText('Pengeluaran Cabang')
            ->assertDontSeeText('Laporan Cabang')
            ->assertDontSee('href="/sales"', false);
    }
}
