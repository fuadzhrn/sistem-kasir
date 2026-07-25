<?php

namespace Tests\Feature\Cashier;

class CashierProductSearchTest extends CashierTestCase
{
    public function test_endpoint_requires_login_and_all_roles_can_search_authorized_branch(): void
    {
        $branch = $this->createBranch('FIND');
        $product = $this->createProduct(['name' => 'Pestisida Pencarian']);
        $this->createStock($branch, $product, '7.000');
        $users = [
            $this->createUser('owner'),
            $this->createUser('admin', $branch),
            $this->createUser('cashier', $branch),
        ];

        $this->getJson(route('cashier.products.index'))->assertUnauthorized();

        foreach ($users as $user) {
            $this->actingAs($user)->getJson(route('cashier.products.index', $this->endpointParams(
                $user,
                $branch,
                ['search' => 'Pestisida Pencarian'],
            )))->assertOk()
                ->assertJsonPath('data.0.id', $product->id)
                ->assertJsonPath('data.0.stock_quantity', '7.000');
        }
    }

    public function test_search_name_code_barcode_brand_and_size_works(): void
    {
        $branch = $this->createBranch('SRCH');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'code' => 'HERB-991',
            'barcode' => '8991234567890',
            'name' => 'Herbisida Unggul',
            'brand' => 'AgroHebat',
            'size' => '750 ml',
        ]);
        $this->createStock($branch, $product);

        foreach (['Herbisida', 'HERB-991', '8991234567890', 'AgroHebat', '750 ml'] as $term) {
            $this->actingAs($owner)->getJson(route('cashier.products.index', [
                'branch_id' => $branch->id,
                'search' => $term,
            ]))->assertOk()->assertJsonPath('data.0.id', $product->id);
        }
    }

    public function test_exact_barcode_is_prioritized_above_name_matches(): void
    {
        $branch = $this->createBranch('BAR');
        $owner = $this->createUser('owner');
        $nameMatch = $this->createProduct(['name' => 'Produk 8990001112223']);
        $exact = $this->createProduct(['barcode' => '8990001112223', 'name' => 'Produk Exact']);
        $this->createStock($branch, $nameMatch);
        $this->createStock($branch, $exact);

        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branch->id,
            'search' => '8990001112223',
        ]))->assertOk()->assertJsonPath('data.0.id', $exact->id);
    }

    public function test_category_filter_and_active_product_category_unit_rules_work(): void
    {
        $branch = $this->createBranch('FLT');
        $owner = $this->createUser('owner');
        $categoryA = $this->createCategory(['name' => 'Kategori A']);
        $categoryB = $this->createCategory(['name' => 'Kategori B']);
        $active = $this->createProduct(['category_id' => $categoryA->id]);
        $other = $this->createProduct(['category_id' => $categoryB->id]);
        $inactiveProduct = $this->createProduct(['category_id' => $categoryA->id, 'is_active' => false]);
        $inactiveCategory = $this->createCategory(['is_active' => false]);
        $categoryInactiveProduct = $this->createProduct(['category_id' => $inactiveCategory->id]);
        $inactiveUnit = $this->createUnit(['is_active' => false]);
        $unitInactiveProduct = $this->createProduct(['category_id' => $categoryA->id, 'unit_id' => $inactiveUnit->id]);

        foreach ([$active, $other, $inactiveProduct, $categoryInactiveProduct, $unitInactiveProduct] as $product) {
            $this->createStock($branch, $product);
        }

        $response = $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branch->id,
            'category_id' => $categoryA->id,
        ]))->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertSame([$active->id], $ids->all());
    }

    public function test_missing_stock_is_zero_and_availability_and_status_come_from_backend(): void
    {
        $branch = $this->createBranch('STK');
        $owner = $this->createUser('owner');
        $missing = $this->createProduct(['code' => 'ZERO']);
        $low = $this->createProduct(['code' => 'LOW']);
        $safe = $this->createProduct(['code' => 'SAFE']);
        $this->createStock($branch, $low, '3.000');
        $this->createStock($branch, $safe, '10.000');

        foreach ([
            ['code' => 'ZERO', 'id' => $missing->id, 'quantity' => '0.000', 'status' => 'out', 'available' => false],
            ['code' => 'LOW', 'id' => $low->id, 'quantity' => '3.000', 'status' => 'low', 'available' => true],
            ['code' => 'SAFE', 'id' => $safe->id, 'quantity' => '10.000', 'status' => 'safe', 'available' => true],
        ] as $case) {
            $this->actingAs($owner)->getJson(route('cashier.products.index', [
                'branch_id' => $branch->id,
                'search' => $case['code'],
            ]))->assertOk()
                ->assertJsonPath('data.0.id', $case['id'])
                ->assertJsonPath('data.0.stock_quantity', $case['quantity'])
                ->assertJsonPath('data.0.stock_status', $case['status'])
                ->assertJsonPath('data.0.is_available', $case['available']);
        }
    }

    public function test_pagination_and_validation_limits_work(): void
    {
        $branch = $this->createBranch('PAGE');
        $owner = $this->createUser('owner');

        foreach (range(1, 5) as $index) {
            $this->createProduct(['code' => 'PAGE-'.$index]);
        }

        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branch->id,
            'per_page' => 2,
            'page' => 2,
        ]))->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonCount(2, 'data');

        $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branch->id,
            'per_page' => 41,
            'search' => str_repeat('a', 101),
        ]))->assertUnprocessable();
    }
}
