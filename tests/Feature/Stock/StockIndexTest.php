<?php

namespace Tests\Feature\Stock;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockIndexTest extends StockTestCase
{
    public function test_owner_can_see_all_branch_summary_and_select_each_branch(): void
    {
        $branchA = $this->createBranch('A001');
        $branchB = $this->createBranch('B001');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['name' => 'Pestisida Audit']);
        $this->createStock($branchA, $product, '8.000');

        $this->actingAs($owner)
            ->get(route('stocks.index'))
            ->assertOk()
            ->assertSee('Ringkasan Stok Semua Cabang')
            ->assertSee($branchA->name)
            ->assertSee($branchB->name);

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branchA->id]))
            ->assertOk()
            ->assertSee('Pestisida Audit')
            ->assertSee('8');

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branchB->id]))
            ->assertOk()
            ->assertSee('Pestisida Audit')
            ->assertSee('Habis');
    }

    public function test_admin_only_sees_own_branch_and_cannot_send_another_branch_id(): void
    {
        $branchA = $this->createBranch('A002');
        $branchB = $this->createBranch('B002');
        $admin = $this->createUser('admin', $branchA);
        $product = $this->createProduct(['name' => 'Produk Cabang Aman']);
        $this->createStock($branchA, $product, '9.000');

        $this->actingAs($admin)
            ->get(route('stocks.index'))
            ->assertOk()
            ->assertSee($branchA->name)
            ->assertDontSee($branchB->name)
            ->assertDontSee('Semua cabang');

        $this->actingAs($admin)
            ->get(route('stocks.index', ['branch_id' => $branchB->id]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_cashier_is_forbidden_and_guest_is_redirected_to_login(): void
    {
        $branch = $this->createBranch('A003');
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($cashier)->get(route('stocks.index'))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get(route('stocks.index'))->assertRedirect(route('login'));
    }

    public function test_products_without_stock_are_listed_as_zero_and_out(): void
    {
        $branch = $this->createBranch('A004');
        $owner = $this->createUser('owner');
        $this->createProduct(['code' => 'ZERO-001', 'name' => 'Produk Tanpa Stok']);

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('ZERO-001')
            ->assertSee('Produk Tanpa Stok')
            ->assertSee('Habis');
    }

    public function test_search_category_status_filters_and_pagination_work(): void
    {
        $branch = $this->createBranch('A005');
        $owner = $this->createUser('owner');
        $category = Category::factory()->create(['name' => 'Pupuk Pilihan']);
        $safe = $this->createProduct([
            'category_id' => $category->id,
            'code' => 'FIND-CODE',
            'barcode' => '9988776655',
            'name' => 'Nama Mudah Dicari',
        ]);
        $low = $this->createProduct(['code' => 'LOW-ONLY']);
        $this->createStock($branch, $safe, '6.000');
        $this->createStock($branch, $low, '5.000');
        Product::factory()->count(21)->create(['is_active' => true]);

        foreach (['FIND-CODE', '9988776655', 'Nama Mudah'] as $term) {
            $this->actingAs($owner)
                ->get(route('stocks.index', ['branch_id' => $branch->id, 'search' => $term]))
                ->assertOk()
                ->assertSee('FIND-CODE');
        }

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'category_id' => $category->id]))
            ->assertSee('FIND-CODE')
            ->assertDontSee('LOW-ONLY');

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'status' => 'safe']))
            ->assertSee('FIND-CODE')
            ->assertDontSee('LOW-ONLY');

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'status' => 'low']))
            ->assertSee('LOW-ONLY')
            ->assertDontSee('FIND-CODE');

        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id, 'page' => 2]))
            ->assertOk()
            ->assertSee('Pagination stok');
    }

    public function test_admin_html_does_not_contain_cost_fields_or_values(): void
    {
        $branch = $this->createBranch('A006');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct(['purchase_price' => '87654321.00']);
        $this->createStock($branch, $product, '7.000', '76543210.00');

        $response = $this->actingAs($admin)->get(route('stocks.index'));

        $response->assertOk()
            ->assertDontSee('average_cost')
            ->assertDontSee('unit_cost')
            ->assertDontSee('purchase_price')
            ->assertDontSee('87654321')
            ->assertDontSee('76543210');
    }

    public function test_stock_list_query_count_does_not_grow_with_product_rows(): void
    {
        $branch = $this->createBranch('A007');
        $owner = $this->createUser('owner');
        $this->createProduct();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id]))
            ->assertOk();
        $singleProductQueryCount = count(DB::getQueryLog());

        Product::factory()->count(25)->create(['is_active' => true]);
        DB::flushQueryLog();
        $this->actingAs($owner)
            ->get(route('stocks.index', ['branch_id' => $branch->id]))
            ->assertOk();
        $manyProductsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($singleProductQueryCount + 2, $manyProductsQueryCount);
    }
}
