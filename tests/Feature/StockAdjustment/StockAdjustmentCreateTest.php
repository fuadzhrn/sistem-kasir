<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\StockAdjustment;

class StockAdjustmentCreateTest extends StockAdjustmentTestCase
{
    public function test_owner_can_create_for_any_branch_and_admin_uses_account_branch(): void
    {
        $branchA = $this->createBranch('CA');
        $branchB = $this->createBranch('CB');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $product = $this->createProduct();
        $this->createStock($branchA, $product);
        $this->createStock($branchB, $product);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branchB, $product))
            ->assertRedirect();

        $adminPayload = $this->payload($branchA, $product);
        unset($adminPayload['branch_id']);
        $this->actingAs($admin)->post(route('stock-adjustments.store'), $adminPayload)
            ->assertRedirect();

        $this->assertDatabaseHas('stock_adjustments', ['branch_id' => $branchB->id, 'created_by' => $owner->id]);
        $this->assertDatabaseHas('stock_adjustments', ['branch_id' => $branchA->id, 'created_by' => $admin->id]);
    }

    public function test_form_contains_five_types_active_products_stock_preview_and_confirmation(): void
    {
        $branch = $this->createBranch('FORM');
        $owner = $this->createUser('owner');
        $active = $this->createProduct(['name' => 'Produk Aktif', 'purchase_price' => '918273.00']);
        $inactive = $this->createProduct(['name' => 'Produk Nonaktif', 'is_active' => false]);
        $this->createStock($branch, $active, '12.500');

        $this->actingAs($owner)->get(route('stock-adjustments.create'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee('918273')
            ->assertSee('Tambah Stok')
            ->assertSee('Kurangi Stok')
            ->assertSee('Stok Rusak')
            ->assertSee('Stok Hilang')
            ->assertSee('Koreksi Stok')
            ->assertSee('id="adjustment-confirmation-modal"', false)
            ->assertSee('Simpan Penyesuaian Stok?')
            ->assertSee('data-confirm-adjustment', false);
    }

    public function test_validation_requires_reason_correct_quantity_field_and_precision(): void
    {
        $branch = $this->createBranch('VAL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'reason' => 'test',
            'quantity' => '-1',
        ]))->assertSessionHasErrors(['reason', 'quantity']);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'quantity' => '1.0001',
        ]))->assertSessionHasErrors('quantity');

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'adjustment_type' => StockAdjustment::TYPE_CORRECTION,
            'quantity' => '1',
            'target_quantity' => null,
        ]))->assertSessionHasErrors(['quantity', 'target_quantity']);

        $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
            'adjustment_type' => StockAdjustment::TYPE_ADDITION,
            'target_quantity' => '12',
        ]))->assertSessionHasErrors('target_quantity');
    }

    public function test_adjustment_number_is_backend_generated_unique_and_sequential(): void
    {
        $branch = $this->createBranch('N-01');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);

        foreach (['PALSU-1', 'PALSU-2'] as $spoofedNumber) {
            $this->actingAs($owner)->post(route('stock-adjustments.store'), $this->payload($branch, $product, [
                'adjustment_number' => $spoofedNumber,
            ]))->assertRedirect();
        }

        $dateSegment = now()->format('Ymd');

        $this->assertDatabaseHas('stock_adjustments', ['adjustment_number' => "ADJ-N01-{$dateSegment}-0001"]);
        $this->assertDatabaseHas('stock_adjustments', ['adjustment_number' => "ADJ-N01-{$dateSegment}-0002"]);
        $this->assertDatabaseMissing('stock_adjustments', ['adjustment_number' => 'PALSU-1']);
    }
}
