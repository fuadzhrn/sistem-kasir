<?php

namespace Tests\Feature\Stock;

use App\Models\Category;
use App\Models\StockMovement;
use Carbon\Carbon;

class StockHistoryTest extends StockTestCase
{
    public function test_owner_sees_all_movements_and_can_filter_each_branch(): void
    {
        $branchA = $this->createBranch('HA01');
        $branchB = $this->createBranch('HB01');
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'HISTORY-A']);
        $productB = $this->createProduct(['code' => 'HISTORY-B']);
        $this->createMovement($branchA, $productA, $owner, attributes: ['notes' => 'Riwayat cabang alfa']);
        $this->createMovement($branchB, $productB, $owner, attributes: ['notes' => 'Riwayat cabang beta']);

        $this->actingAs($owner)
            ->get(route('stocks.history.index'))
            ->assertOk()
            ->assertSee('HISTORY-A')
            ->assertSee('HISTORY-B');

        $this->actingAs($owner)
            ->get(route('stocks.history.index', ['branch_id' => $branchA->id]))
            ->assertSee('Riwayat cabang alfa')
            ->assertDontSee('Riwayat cabang beta');

        $this->actingAs($owner)
            ->get(route('stocks.history.index', ['branch_id' => $branchB->id]))
            ->assertSee('Riwayat cabang beta')
            ->assertDontSee('Riwayat cabang alfa');
    }

    public function test_admin_sees_only_own_branch_and_no_cost_information(): void
    {
        $branchA = $this->createBranch('HC01');
        $branchB = $this->createBranch('HD01');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'ADMIN-HISTORY']);
        $productB = $this->createProduct(['code' => 'HIDDEN-HISTORY']);
        $this->createMovement($branchA, $productA, $admin, attributes: ['unit_cost' => '98765432.00']);
        $this->createMovement($branchB, $productB, $owner, attributes: [
            'notes' => 'Movement pembanding cabang lain',
        ]);

        $response = $this->actingAs($admin)->get(route('stocks.history.index'));

        $response->assertOk()
            ->assertSee('ADMIN-HISTORY')
            ->assertDontSee('Movement pembanding cabang lain')
            ->assertDontSee('98765432')
            ->assertDontSee('unit_cost')
            ->assertDontSee('average_cost');

        $this->actingAs($admin)
            ->get(route('stocks.history.index', ['branch_id' => $branchB->id]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_history_filters_search_category_type_user_and_dates(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');
        $branch = $this->createBranch('HE01');
        $owner = $this->createUser('owner');
        $otherUser = $this->createUser('admin', $branch);
        $category = Category::factory()->create(['name' => 'Kategori Riwayat']);
        $match = $this->createProduct(['category_id' => $category->id, 'code' => 'FILTER-ME', 'name' => 'Produk Filter']);
        $other = $this->createProduct(['code' => 'OTHER-MOVE']);
        $this->createMovement($branch, $match, $owner, attributes: ['notes' => 'Movement yang dicari']);

        Carbon::setTestNow('2026-07-22 10:00:00');
        $this->createMovement($branch, $other, $otherUser, StockMovement::TYPE_PURCHASE, [
            'notes' => 'Movement pembanding',
        ]);
        Carbon::setTestNow();

        $base = ['branch_id' => $branch->id];

        foreach ([
            ['search' => 'FILTER-ME'],
            ['search' => 'Produk Filter'],
            ['product_id' => $match->id],
            ['category_id' => $category->id],
            ['movement_type' => StockMovement::TYPE_INITIAL],
            ['user_id' => $owner->id],
            ['date_from' => '2026-07-20', 'date_to' => '2026-07-20'],
        ] as $filter) {
            $this->actingAs($owner)
                ->get(route('stocks.history.index', [...$base, ...$filter]))
                ->assertOk()
                ->assertSee('Movement yang dicari')
                ->assertDontSee('Movement pembanding');
        }
    }

    public function test_history_is_newest_first_and_paginated(): void
    {
        $branch = $this->createBranch('HF01');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['code' => 'PAGED-HISTORY']);

        for ($index = 1; $index <= 22; $index++) {
            Carbon::setTestNow("2026-07-{$index} 10:00:00");
            $this->createMovement($branch, $product, $owner, attributes: [
                'notes' => sprintf('Movement urutan [%03d]', $index),
                'quantity_before' => (string) ($index - 1).'.000',
                'quantity_change' => '1.000',
                'quantity_after' => (string) $index.'.000',
            ]);
        }
        Carbon::setTestNow();

        $response = $this->actingAs($owner)
            ->get(route('stocks.history.index', ['branch_id' => $branch->id]));

        $response->assertOk()->assertSee('Movement urutan [022]')->assertDontSee('Movement urutan [001]');
        $this->assertLessThan(
            strpos($response->getContent(), 'Movement urutan [021]'),
            strpos($response->getContent(), 'Movement urutan [022]'),
        );
        $this->actingAs($owner)
            ->get(route('stocks.history.index', ['branch_id' => $branch->id, 'page' => 2]))
            ->assertOk()
            ->assertSee('Movement urutan [001]');
    }

    public function test_cashier_is_forbidden_and_no_edit_or_delete_routes_exist(): void
    {
        $branch = $this->createBranch('HG01');
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($cashier)->get(route('stocks.history.index'))->assertForbidden();

        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => [$route->uri(), $route->methods()]);
        $this->assertFalse($routes->contains(fn (array $route) => str_starts_with($route[0], 'stocks')
            && (in_array('PUT', $route[1], true) || in_array('PATCH', $route[1], true) || in_array('DELETE', $route[1], true))));
    }
}
