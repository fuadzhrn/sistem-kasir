<?php

namespace Tests\Feature\Cashier;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;

class CashierPageTest extends CashierTestCase
{
    public function test_owner_without_branch_sees_selector_and_owner_with_branch_sees_workspace(): void
    {
        $branch = $this->createBranch('OWN');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('Pilih Cabang Kasir')
            ->assertSee('data-branch-selector', false);

        $this->actingAs($owner)->get(route('cashier.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('Kasir '.$branch->name)
            ->assertSee('data-branch-id="'.$branch->id.'"', false);
    }

    public function test_admin_and_cashier_open_workspace_for_account_branch(): void
    {
        $branch = $this->createBranch('ACC');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        foreach ([$admin, $cashier] as $user) {
            $this->actingAs($user)->get(route('cashier.index'))
                ->assertOk()
                ->assertSee($branch->name)
                ->assertSee('cabang ditentukan secara aman dari akun Anda')
                ->assertDontSee('data-branch-selector', false);
        }
    }

    public function test_page_loads_only_active_categories_and_payment_methods_in_order(): void
    {
        $branch = $this->createBranch('DATA');
        $owner = $this->createUser('owner');
        $activeCategory = $this->createCategory(['name' => 'Pestisida Aktif']);
        $inactiveCategory = $this->createCategory(['name' => 'Kategori Mati', 'is_active' => false]);
        $this->createProduct(['category_id' => $activeCategory->id]);
        $this->createProduct(['category_id' => $inactiveCategory->id]);
        $this->createPaymentMethod(['code' => 'QRIS', 'name' => 'QRIS Aktif', 'type' => 'non_cash', 'sort_order' => 2]);
        $this->createPaymentMethod(['code' => 'CASH', 'name' => 'Tunai Aktif', 'sort_order' => 1]);
        $this->createPaymentMethod(['code' => 'OFF', 'name' => 'Pembayaran Mati', 'is_active' => false]);

        $response = $this->actingAs($owner)->get(route('cashier.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('Pestisida Aktif')
            ->assertDontSee('Kategori Mati')
            ->assertSeeInOrder(['Tunai Aktif', 'QRIS Aktif'])
            ->assertDontSee('Pembayaran Mati');
    }

    public function test_page_is_simulation_only_and_opening_it_does_not_change_database(): void
    {
        $branch = $this->createBranch('SAFE');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '8.000');
        $this->createPaymentMethod();
        $before = [
            'sales' => Sale::query()->count(),
            'items' => SaleItem::query()->count(),
            'movements' => StockMovement::query()->count(),
        ];

        $this->actingAs($cashier)->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('Mode Desain Kasir')
            ->assertSee('Bayar &amp; Cetak', false)
            ->assertSee('Bayar Tanpa Cetak')
            ->assertSee('data-payment-action="print"', false)
            ->assertDontSee('action="/sales"', false);

        $this->assertSame($before['sales'], Sale::query()->count());
        $this->assertSame($before['items'], SaleItem::query()->count());
        $this->assertSame($before['movements'], StockMovement::query()->count());
        $this->assertSame('8.000', $stock->refresh()->quantity);
    }
}
