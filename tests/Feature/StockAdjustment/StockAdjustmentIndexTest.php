<?php

namespace Tests\Feature\StockAdjustment;

class StockAdjustmentIndexTest extends StockAdjustmentTestCase
{
    public function test_owner_can_view_search_and_filter_all_adjustments(): void
    {
        $branchA = $this->createBranch('IA');
        $branchB = $this->createBranch('IB');
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'PUPUK-A', 'name' => 'Pupuk Urea']);
        $productB = $this->createProduct(['code' => 'OBAT-B', 'name' => 'Obat Gulma']);
        $adjustmentA = $this->createAdjustment($branchA, $productA, $owner, [
            'adjustment_number' => 'ADJ-IA-20260724-0001',
            'adjustment_type' => 'addition',
            'created_at' => '2026-07-24 10:00:00',
        ]);
        $adjustmentB = $this->createAdjustment($branchB, $productB, $owner, [
            'adjustment_number' => 'ADJ-IB-20260725-0001',
            'adjustment_type' => 'damaged',
            'quantity_change' => '-2.000',
            'quantity_after' => '8.000',
        ]);

        $this->actingAs($owner)->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee($adjustmentA->adjustment_number)
            ->assertSee($adjustmentB->adjustment_number);

        $this->actingAs($owner)->get(route('stock-adjustments.index', ['branch_id' => $branchA->id]))
            ->assertOk()->assertSee($adjustmentA->adjustment_number)->assertDontSee($adjustmentB->adjustment_number);

        $this->actingAs($owner)->get(route('stock-adjustments.index', ['search' => 'Obat Gulma']))
            ->assertOk()->assertSee($adjustmentB->adjustment_number)->assertDontSee($adjustmentA->adjustment_number);

        $this->actingAs($owner)->get(route('stock-adjustments.index', [
            'adjustment_type' => 'addition',
            'date_from' => '2026-07-24',
            'date_to' => '2026-07-24',
        ]))->assertOk()->assertSee($adjustmentA->adjustment_number)->assertDontSee($adjustmentB->adjustment_number);
    }

    public function test_admin_sees_only_own_branch_without_cost_and_index_is_paginated(): void
    {
        $branchA = $this->createBranch('PA');
        $branchB = $this->createBranch('PB');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $own = $this->createAdjustment($branchA, $product, $admin, [
            'adjustment_number' => 'ADJ-PA-20260725-0001',
            'unit_cost' => '987654.00',
        ]);
        $other = $this->createAdjustment($branchB, $product, $owner, [
            'adjustment_number' => 'ADJ-PB-20260725-0001',
        ]);

        $this->actingAs($admin)->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee($own->adjustment_number)
            ->assertDontSee($other->adjustment_number)
            ->assertDontSee('987654')
            ->assertDontSee('name="branch_id"', false);

        for ($sequence = 2; $sequence <= 21; $sequence++) {
            $this->createAdjustment($branchA, $product, $admin, [
                'adjustment_number' => 'ADJ-PA-20260725-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($admin)->get(route('stock-adjustments.index'))
            ->assertOk()
            ->assertSee('Menampilkan 1-20 dari 21');
    }
}
