<?php

namespace Tests\Feature\StockReceipt;

use App\Models\StockReceipt;

class StockReceiptCreateTest extends StockReceiptTestCase
{
    public function test_owner_can_create_receipts_for_each_active_branch_and_admin_uses_account_branch(): void
    {
        $branchA = $this->createBranch('CA1');
        $branchB = $this->createBranch('CB1');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branchA);
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branchB, $product))
            ->assertRedirect();

        $adminPayload = $this->payload($branchA, $product);
        unset($adminPayload['branch_id']);
        $this->actingAs($admin)->post(route('stock-receipts.store'), $adminPayload)
            ->assertRedirect();

        $this->assertDatabaseHas('stock_receipts', ['branch_id' => $branchB->id, 'created_by' => $owner->id]);
        $this->assertDatabaseHas('stock_receipts', ['branch_id' => $branchA->id, 'created_by' => $admin->id]);
        $this->assertSame(2, StockReceipt::query()->count());
    }

    public function test_create_form_has_only_active_products_safe_fields_and_confirmation_ui(): void
    {
        $branch = $this->createBranch('FORM');
        $owner = $this->createUser('owner');
        $active = $this->createProduct(['name' => 'Produk Aktif', 'purchase_price' => '987654.00']);
        $inactive = $this->createProduct(['name' => 'Produk Nonaktif', 'is_active' => false]);

        $this->actingAs($owner)->get(route('stock-receipts.create'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee('987654')
            ->assertSee('Konfirmasi Barang Masuk')
            ->assertSee('data-add-receipt-item', false);
    }

    public function test_header_item_precision_duplicate_and_limit_validation(): void
    {
        $branch = $this->createBranch('VAL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'receipt_date' => null,
            'items' => [],
        ]))->assertSessionHasErrors(['receipt_date', 'items']);

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'receipt_date' => now()->addDay()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => '1.0000', 'purchase_price' => '10000,01'],
                ['product_id' => $product->id, 'quantity' => '0', 'purchase_price' => '0'],
            ],
        ]))->assertSessionHasErrors([
            'receipt_date',
            'items.0.product_id',
            'items.0.quantity',
            'items.0.purchase_price',
            'items.1.product_id',
            'items.1.quantity',
            'items.1.purchase_price',
        ]);

        $tooMany = array_fill(0, 101, [
            'product_id' => $product->id,
            'quantity' => '1',
            'purchase_price' => '100',
        ]);
        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'items' => $tooMany,
        ]))->assertSessionHasErrors('items');
    }

    public function test_fractional_quantity_and_grouped_integer_rupiah_are_accepted(): void
    {
        $branch = $this->createBranch('DEC');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'supplier_name' => '',
            'notes' => '',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '2.500',
                'purchase_price' => '20.000',
            ]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('stock_receipt_items', [
            'product_id' => $product->id,
            'quantity' => '2.500',
            'purchase_price' => '20000.00',
            'subtotal' => '50000.00',
        ]);
        $this->assertDatabaseHas('stock_receipts', [
            'supplier_name' => null,
            'notes' => null,
            'total_cost' => '50000.00',
        ]);
    }

    public function test_fractional_rupiah_purchase_price_is_rejected(): void
    {
        $branch = $this->createBranch('RUP');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        $this->actingAs($owner)->post(route('stock-receipts.store'), $this->payload($branch, $product, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '1',
                'purchase_price' => '20000.50',
            ]],
        ]))->assertSessionHasErrors('items.0.purchase_price');
    }
}
